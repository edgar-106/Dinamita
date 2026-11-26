<?php
/**
 * Procesador seguro de video e imágenes para recorridos por escenas.
 * INFONATEC Inmobiliaria
 */

class VideoProcessor {
    public const DEFAULT_MAX_VIDEO_MB = 35;
    public const ALLOWED_VIDEO_MIMES = [
        'video/mp4'       => 'mp4',
        'video/quicktime' => 'mov',
        'video/webm'      => 'webm',
    ];

    /**
     * Convierte valores de php.ini (ej. "40M", "512K", "1G") a bytes.
     */
    public static function parseIniBytes(string $raw): int {
        $val = trim($raw);
        if ($val === '' || $val === '0' || $val === '-1') {
            return 0;
        }
        $unit = strtolower(substr($val, -1));
        $num = (float) $val;
        return match ($unit) {
            'g' => (int) ($num * 1073741824),
            'm' => (int) ($num * 1048576),
            'k' => (int) ($num * 1024),
            default => (int) $num,
        };
    }

    /**
     * Devuelve el límite efectivo en bytes combinando VIDEO_MAX_MB y php.ini.
     */
    public static function getEffectiveMaxVideoBytes(): int {
        $envMb = (int) env('VIDEO_MAX_MB', (string) self::DEFAULT_MAX_VIDEO_MB);
        if ($envMb < 1 || $envMb > 200) {
            $envMb = self::DEFAULT_MAX_VIDEO_MB;
        }
        $limit = $envMb * 1048576;

        $iniUpload = self::parseIniBytes((string) ini_get('upload_max_filesize'));
        $iniPost = self::parseIniBytes((string) ini_get('post_max_size'));

        if ($iniUpload > 0) {
            $limit = min($limit, $iniUpload);
        }
        if ($iniPost > 0) {
            // Reservar 1 MB para campos de formulario y cabecera multipart.
            $safePost = max(1048576, $iniPost - 1048576);
            $limit = min($limit, $safePost);
        }

        return max(1048576, $limit);
    }

    /**
     * Busca el binario FFmpeg o FFprobe en .env, PATH o rutas comunes de Windows/XAMPP.
     */
    public static function resolveBinary(string $envKey, string $name): ?string {
        $configured = trim((string) env($envKey, ''));
        if ($configured !== '') {
            return is_file($configured) ? $configured : null;
        }

        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $exeName = $isWindows && !str_ends_with(strtolower($name), '.exe') ? $name . '.exe' : $name;

        $pathDirs = explode(PATH_SEPARATOR, (string) (getenv('PATH') ?: ''));
        foreach ($pathDirs as $dir) {
            $dir = trim($dir, " \t\n\r\0\x0B\"");
            if ($dir === '') {
                continue;
            }
            $candidate = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $exeName;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $commonDirs = $isWindows
            ? ['C:\\ffmpeg\\bin', 'C:\\xampp\\ffmpeg\\bin', 'C:\\Program Files\\ffmpeg\\bin', 'C:\\tools\\ffmpeg\\bin']
            : ['/usr/bin', '/usr/local/bin', '/opt/homebrew/bin'];

        foreach ($commonDirs as $dir) {
            $candidate = $dir . DIRECTORY_SEPARATOR . $exeName;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Estado de disponibilidad del procesador de video.
     */
    public static function getStatus(): array {
        $ffmpeg = self::resolveBinary('FFMPEG_PATH', 'ffmpeg');
        $ffprobe = self::resolveBinary('FFPROBE_PATH', 'ffprobe');
        $maxBytes = self::getEffectiveMaxVideoBytes();

        return [
            'ffmpeg_available'   => $ffmpeg !== null,
            'ffprobe_available'  => $ffprobe !== null,
            'max_video_bytes'    => $maxBytes,
            'max_video_mb'       => round($maxBytes / 1048576, 1),
            'allowed_extensions' => array_values(self::ALLOWED_VIDEO_MIMES),
            'allowed_mimes'      => array_keys(self::ALLOWED_VIDEO_MIMES),
            'message'            => $ffmpeg !== null
                ? 'FFmpeg disponible en el servidor para extracción de fotogramas.'
                : 'FFmpeg no está instalado en el servidor. Se usará captura asistida desde el navegador o configura FFMPEG_PATH en .env (consulta docs/INSTALACION_VIDEO_FFMPEG.md).',
        ];
    }

    /**
     * Ejecuta un proceso externo con arreglo de argumentos (sin shell) y timeout estricto.
     *
     * @param string[] $command
     * @return array{ok:bool, timed_out:bool, exit_code:int, stdout:string, stderr:string}
     */
    public static function runProcess(array $command, int $timeoutSeconds = 15): array {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $options = DIRECTORY_SEPARATOR === '\\' ? ['bypass_shell' => true] : [];
        $process = @proc_open($command, $descriptors, $pipes, null, null, $options);

        if (!is_resource($process)) {
            return [
                'ok'        => false,
                'timed_out' => false,
                'exit_code' => -1,
                'stdout'    => '',
                'stderr'    => 'No se pudo iniciar el proceso externo.',
            ];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = microtime(true);
        $timedOut = false;

        while (true) {
            $status = proc_get_status($process);
            $outChunk = stream_get_contents($pipes[1]);
            $errChunk = stream_get_contents($pipes[2]);
            if (is_string($outChunk)) {
                $stdout .= $outChunk;
            }
            if (is_string($errChunk)) {
                $stderr .= $errChunk;
            }

            if (empty($status['running'])) {
                break;
            }

            if ((microtime(true) - $start) >= $timeoutSeconds) {
                $timedOut = true;
                @proc_terminate($process, 9);
                break;
            }

            usleep(25000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'ok'        => !$timedOut && $exitCode === 0,
            'timed_out' => $timedOut,
            'exit_code' => $exitCode,
            'stdout'    => $stdout,
            'stderr'    => $stderr,
        ];
    }

    /**
     * Verifica firma binaria mínima del contenedor de video para rechazar archivos falsificados.
     */
    public static function validateVideoHeader(string $filePath, string $mime): bool {
        $fh = @fopen($filePath, 'rb');
        if (!$fh) {
            return false;
        }
        $bytes = fread($fh, 32);
        fclose($fh);

        if (!is_string($bytes) || strlen($bytes) < 12) {
            return false;
        }

        if ($mime === 'video/mp4' || $mime === 'video/quicktime') {
            return substr($bytes, 4, 4) === 'ftyp';
        }
        if ($mime === 'video/webm') {
            return substr($bytes, 0, 4) === "\x1A\x45\xDF\xA3";
        }

        return false;
    }

    /**
     * Inspecciona duración y dimensiones con FFprobe si está disponible.
     */
    public static function probeVideo(string $videoPath): array {
        $ffprobe = self::resolveBinary('FFPROBE_PATH', 'ffprobe');
        if ($ffprobe === null) {
            return [
                'probed'   => false,
                'valid'    => true,
                'duration' => null,
                'width'    => null,
                'height'   => null,
            ];
        }

        $res = self::runProcess([
            $ffprobe,
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=codec_type,width,height:format=duration',
            '-of', 'json',
            $videoPath,
        ], 10);

        if (!$res['ok']) {
            return [
                'probed'   => true,
                'valid'    => false,
                'duration' => null,
                'width'    => null,
                'height'   => null,
                'message'  => $res['timed_out']
                    ? 'El análisis del video excedió el tiempo máximo permitido.'
                    : 'No se encontró una pista de video válida en el archivo subido.',
            ];
        }

        $json = json_decode($res['stdout'], true);
        $stream = $json['streams'][0] ?? null;
        if (!is_array($stream) || ($stream['codec_type'] ?? '') !== 'video') {
            return [
                'probed'   => true,
                'valid'    => false,
                'duration' => null,
                'width'    => null,
                'height'   => null,
                'message'  => 'El archivo no contiene un stream de video reproducible.',
            ];
        }

        $duration = isset($json['format']['duration']) ? round((float) $json['format']['duration'], 2) : null;
        $width = isset($stream['width']) ? (int) $stream['width'] : null;
        $height = isset($stream['height']) ? (int) $stream['height'] : null;

        return [
            'probed'   => true,
            'valid'    => true,
            'duration' => ($duration !== null && $duration > 0) ? $duration : null,
            'width'    => ($width !== null && $width > 0) ? $width : null,
            'height'   => ($height !== null && $height > 0) ? $height : null,
        ];
    }

    /**
     * Extrae un fotograma JPEG en el segundo indicado hacia un archivo temporal seguro.
     */
    public static function extractFrame(string $videoPath, float $second, string $destTempPath): array {
        $ffmpeg = self::resolveBinary('FFMPEG_PATH', 'ffmpeg');
        if ($ffmpeg === null) {
            return [
                'ok'      => false,
                'code'    => 'ffmpeg_unavailable',
                'message' => 'FFmpeg no está instalado o configurado en el servidor. Instala FFmpeg (ver docs/INSTALACION_VIDEO_FFMPEG.md) o utiliza la captura desde el reproductor.',
            ];
        }

        if (!is_file($videoPath) || !is_readable($videoPath)) {
            return [
                'ok'      => false,
                'code'    => 'video_missing',
                'message' => 'El archivo de video original no está disponible en el servidor.',
            ];
        }

        $seek = sprintf('%.3F', max(0.0, $second));

        try {
            $res = self::runProcess([
                $ffmpeg,
                '-nostdin',
                '-y',
                '-threads', '2',
                '-ss', $seek,
                '-i', $videoPath,
                '-frames:v', '1',
                '-vf', "scale='min(1280,iw)':-2",
                '-q:v', '3',
                $destTempPath,
            ], 15);

            if (!$res['ok'] || !is_file($destTempPath) || filesize($destTempPath) <= 0) {
                @unlink($destTempPath);
                return [
                    'ok'      => false,
                    'code'    => $res['timed_out'] ? 'ffmpeg_timeout' : 'ffmpeg_failed',
                    'message' => $res['timed_out']
                        ? 'La extracción del fotograma excedió el límite de 15 segundos.'
                        : 'No se pudo extraer un fotograma válido en ese segundo del video.',
                ];
            }

            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
            $mime = $finfo ? (string) finfo_file($finfo, $destTempPath) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if ($mime !== 'image/jpeg' || @getimagesize($destTempPath) === false) {
                @unlink($destTempPath);
                return [
                    'ok'      => false,
                    'code'    => 'invalid_extracted_image',
                    'message' => 'El archivo generado por FFmpeg no es una imagen JPEG válida.',
                ];
            }

            return ['ok' => true, 'path' => $destTempPath];
        } catch (Throwable $e) {
            @unlink($destTempPath);
            error_log('Error ejecutando FFmpeg: ' . $e->getMessage());
            return [
                'ok'      => false,
                'code'    => 'ffmpeg_exception',
                'message' => 'Ocurrió un error interno al procesar el fotograma del video.',
            ];
        }
    }

    /**
     * Sirve un archivo de video por fragmentos (HTTP 206 Range) sin cargarlo completo en memoria PHP.
     */
    public static function streamVideoRange(string $filePath, string $mimeType, string $cacheControl = 'private, no-store'): void {
        if (!is_file($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            exit;
        }

        $size = (int) filesize($filePath);
        if ($size <= 0) {
            http_response_code(404);
            exit;
        }

        // Liberar bloqueo de sesión antes de transmitir bytes para no bloquear peticiones paralelas.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $start = 0;
        $end = $size - 1;
        $isPartial = false;
        $rangeHeader = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));

        if ($rangeHeader !== '') {
            if (!preg_match('/^bytes=(\d*)-(\d*)$/i', $rangeHeader, $matches)) {
                header("Content-Range: bytes */{$size}");
                http_response_code(416);
                exit;
            }
            $rangeStart = $matches[1];
            $rangeEnd = $matches[2];

            if ($rangeStart === '' && $rangeEnd === '') {
                header("Content-Range: bytes */{$size}");
                http_response_code(416);
                exit;
            }

            if ($rangeStart === '') {
                $suffix = (int) $rangeEnd;
                if ($suffix <= 0) {
                    header("Content-Range: bytes */{$size}");
                    http_response_code(416);
                    exit;
                }
                $start = max(0, $size - $suffix);
                $end = $size - 1;
            } else {
                $start = (int) $rangeStart;
                $end = ($rangeEnd !== '') ? min($size - 1, (int) $rangeEnd) : ($size - 1);
            }

            if ($start < 0 || $start > $end || $start >= $size) {
                header("Content-Range: bytes */{$size}");
                http_response_code(416);
                exit;
            }

            $isPartial = true;
        }

        $length = ($end - $start) + 1;

        header('Content-Type: ' . $mimeType);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $length);
        header('Content-Disposition: inline');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: ' . $cacheControl);

        if ($isPartial) {
            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$size}");
        } else {
            http_response_code(200);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            exit;
        }

        $fp = @fopen($filePath, 'rb');
        if (!$fp) {
            exit;
        }

        if ($start > 0) {
            fseek($fp, $start);
        }

        $remaining = $length;
        $chunkSize = 65536; // 64 KB
        while ($remaining > 0 && !feof($fp) && !connection_aborted()) {
            $readLen = min($chunkSize, $remaining);
            $buffer = fread($fp, $readLen);
            if ($buffer === false || $buffer === '') {
                break;
            }
            echo $buffer;
            $remaining -= strlen($buffer);
            @flush();
        }

        fclose($fp);
        exit;
    }
}

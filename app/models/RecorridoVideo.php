<?php
/**
 * Modelo RecorridoVideo
 * Gestiona carga segura de videos privados, extracción de fotogramas y streaming
 * autorizado de fragmentos sobre el almacenamiento unificado de recorridos.
 * INFONATEC Inmobiliaria
 */

class RecorridoVideo extends Model {
    public const MAX_VIDEOS_PER_TOUR = 10;
    public const MAX_FILES_PER_TOUR = 60;
    public const MAX_DURATION_SECONDS = 7200.0;
    public const MAX_IMAGE_BYTES = 8388608; // 8 MB, alineado con RecorridoFoto
    public const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private string $rootDir;
    private string $videosDir;
    private string $tmpDir;
    private string $scenesDir;
    private RecorridoFoto $photoModel;

    public function __construct() {
        parent::__construct();
        $this->rootDir = dirname(__DIR__, 2);
        $this->videosDir = $this->rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'recorridos_videos';
        $this->tmpDir = $this->rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'recorridos_tmp';
        $this->scenesDir = $this->rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'recorridos';
        $this->photoModel = new RecorridoFoto();
    }

    private function db(): PDO {
        if ($this->db === null) {
            throw new RuntimeException('Base de datos no disponible.', 503);
        }
        return $this->db;
    }

    private function ensureDirectories(): void {
        foreach ([$this->videosDir, $this->tmpDir, $this->scenesDir] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('No se pudo preparar el directorio de almacenamiento.', 503);
            }
        }
    }

    /**
     * Lista los videos privados disponibles para el propietario autenticado.
     */
    public function listVideos(int $propertyId, int $userId): array {
        $this->photoModel->owner($propertyId, $userId);
        $stmt = $this->db()->prepare(
            "SELECT id_video, id_recorrido, id_propiedad, nombre_original, mime_type,
                    tamano_bytes, duracion_segundos, ancho, alto, fecha_subida
             FROM recorrido_video
             WHERE id_propiedad = ? AND id_propietario = ? AND estado = 'LISTO'
             ORDER BY fecha_subida DESC, id_video DESC"
        );
        $stmt->execute([$propertyId, $userId]);
        $rows = $stmt->fetchAll();

        $videos = [];
        foreach ($rows as $row) {
            $videos[] = $this->formatVideoRow($propertyId, $row);
        }
        return $videos;
    }

    /**
     * Valida y guarda un video privado del propietario sin exponerlo públicamente.
     */
    public function uploadVideo(
        int $propertyId,
        int $userId,
        array $file,
        ?float $clientDuration = null,
        ?int $clientWidth = null,
        ?int $clientHeight = null
    ): array {
        $property = $this->photoModel->owner($propertyId, $userId);
        if ($property['fecha_retiro'] !== null) {
            throw new RuntimeException('No puedes subir videos en un anuncio retirado.', 400);
        }

        $this->ensureDirectories();

        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $maxBytes = VideoProcessor::getEffectiveMaxVideoBytes();
        $maxMb = round($maxBytes / 1048576, 1);

        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException("El video supera el tamaño máximo permitido ({$maxMb} MB).", 400);
        }
        if ($err === UPLOAD_ERR_PARTIAL) {
            throw new RuntimeException('La carga del video quedó incompleta. Inténtalo de nuevo.', 400);
        }
        if ($err === UPLOAD_ERR_NO_FILE || empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            throw new RuntimeException('Selecciona un archivo de video válido (MP4, MOV o WebM).', 400);
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Ocurrió un error al recibir el video en el servidor.', 400);
        }

        $tmpPath = (string) $file['tmp_name'];
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException("El video debe pesar entre 1 byte y {$maxMb} MB.", 400);
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string) finfo_file($finfo, $tmpPath) : (string) (@mime_content_type($tmpPath) ?: '');
        if ($finfo) {
            finfo_close($finfo);
        }

        $ext = VideoProcessor::ALLOWED_VIDEO_MIMES[$mime] ?? null;
        if ($ext === null) {
            throw new RuntimeException('Formato de video no permitido. Usa únicamente MP4, MOV o WebM.', 400);
        }
        if (!VideoProcessor::validateVideoHeader($tmpPath, $mime)) {
            throw new RuntimeException('El archivo no tiene una cabecera válida de video MP4, MOV o WebM.', 400);
        }

        $probe = VideoProcessor::probeVideo($tmpPath);
        if (!empty($probe['probed']) && empty($probe['valid'])) {
            throw new RuntimeException((string) ($probe['message'] ?? 'No se encontró una pista de video válida.'), 400);
        }

        $duration = $probe['duration'] ?? $this->sanitizeOptionalDuration($clientDuration);
        $width = $probe['width'] ?? $this->sanitizeOptionalDimension($clientWidth);
        $height = $probe['height'] ?? $this->sanitizeOptionalDimension($clientHeight);

        $origName = basename((string) ($file['name'] ?? ('video.' . $ext)));
        $origName = preg_replace('/[^\p{L}\p{N}._\-\s]/u', '_', $origName) ?: ('video.' . $ext);
        $origName = mb_substr($origName, 0, 240);

        $internalName = 'vid_' . $propertyId . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $destPath = $this->videosDir . DIRECTORY_SEPARATOR . $internalName;
        $moved = false;

        try {
            $this->db()->beginTransaction();
            $tourId = $this->photoModel->ensureTour($propertyId);

            $countStmt = $this->db()->prepare(
                "SELECT COUNT(*) FROM recorrido_video WHERE id_recorrido = ? AND estado = 'LISTO' FOR UPDATE"
            );
            $countStmt->execute([$tourId]);
            if ((int) $countStmt->fetchColumn() >= self::MAX_VIDEOS_PER_TOUR) {
                throw new RuntimeException('Alcanzaste el límite de ' . self::MAX_VIDEOS_PER_TOUR . ' videos por recorrido.', 400);
            }

            $moved = move_uploaded_file($tmpPath, $destPath);
            if (!$moved) {
                throw new RuntimeException('No se pudo almacenar el video en el servidor.', 503);
            }

            $ins = $this->db()->prepare(
                "INSERT INTO recorrido_video
                 (id_recorrido, id_propiedad, id_propietario, nombre_original, archivo_interno,
                  mime_type, tamano_bytes, duracion_segundos, ancho, alto, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'LISTO')"
            );
            $ins->execute([
                $tourId,
                $propertyId,
                $userId,
                $origName,
                $internalName,
                $mime,
                $size,
                $duration,
                $width,
                $height,
            ]);
            $videoId = (int) $this->db()->lastInsertId();
            $this->db()->commit();

            $rowStmt = $this->db()->prepare(
                'SELECT id_video, id_recorrido, id_propiedad, nombre_original, mime_type,
                        tamano_bytes, duracion_segundos, ancho, alto, fecha_subida
                 FROM recorrido_video WHERE id_video = ?'
            );
            $rowStmt->execute([$videoId]);
            $row = $rowStmt->fetch() ?: [
                'id_video'          => $videoId,
                'id_recorrido'      => $tourId,
                'id_propiedad'      => $propertyId,
                'nombre_original'   => $origName,
                'mime_type'         => $mime,
                'tamano_bytes'      => $size,
                'duracion_segundos' => $duration,
                'ancho'             => $width,
                'alto'              => $height,
                'fecha_subida'      => date('Y-m-d H:i:s'),
            ];

            return $this->formatVideoRow($propertyId, $row);
        } catch (Throwable $e) {
            if ($this->db()->inTransaction()) {
                $this->db()->rollBack();
            }
            if ($moved && is_file($destPath)) {
                @unlink($destPath);
            }
            throw $e;
        }
    }

    /**
     * Devuelve la ruta y MIME de un video privado autorizado exclusivamente para su propietario.
     */
    public function getOwnerVideoFile(int $propertyId, int $userId, int $videoId): array {
        $this->photoModel->owner($propertyId, $userId);
        $stmt = $this->db()->prepare(
            "SELECT archivo_interno, mime_type, nombre_original
             FROM recorrido_video
             WHERE id_video = ? AND id_propiedad = ? AND id_propietario = ? AND estado = 'LISTO'
             LIMIT 1"
        );
        $stmt->execute([$videoId, $propertyId, $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Video no encontrado o sin permiso de acceso.', 404);
        }

        $path = $this->videosDir . DIRECTORY_SEPARATOR . basename((string) $row['archivo_interno']);
        if (!is_file($path)) {
            throw new RuntimeException('Archivo de video no disponible en almacenamiento.', 404);
        }

        return [
            'path'            => $path,
            'mime_type'       => (string) $row['mime_type'],
            'nombre_original' => (string) $row['nombre_original'],
        ];
    }

    /**
     * Elimina un video privado solo si ninguna escena en borrador o publicación lo referencia.
     */
    public function deleteVideo(int $propertyId, int $userId, int $videoId): void {
        $this->photoModel->owner($propertyId, $userId);
        $this->db()->beginTransaction();
        $internalName = null;

        try {
            $tourId = $this->photoModel->ensureTour($propertyId);
            $tourStmt = $this->db()->prepare('SELECT borrador, publicado FROM recorrido_foto WHERE id_recorrido = ? FOR UPDATE');
            $tourStmt->execute([$tourId]);
            $tourRow = $tourStmt->fetch();

            $vidStmt = $this->db()->prepare(
                'SELECT id_video, archivo_interno FROM recorrido_video
                 WHERE id_video = ? AND id_propiedad = ? AND id_propietario = ?
                 LIMIT 1 FOR UPDATE'
            );
            $vidStmt->execute([$videoId, $propertyId, $userId]);
            $videoRow = $vidStmt->fetch();
            if (!$videoRow) {
                throw new RuntimeException('El video no existe o no pertenece a tu cuenta.', 404);
            }

            foreach (['borrador', 'publicado'] as $field) {
                $doc = json_decode((string) ($tourRow[$field] ?? 'null'), true);
                foreach (($doc['escenas'] ?? []) as $scene) {
                    if ((int) ($scene['video_id'] ?? 0) === $videoId) {
                        throw new RuntimeException(
                            'Este video está en uso por escenas en el borrador o en el recorrido publicado. Quita esas escenas y guarda los cambios antes de eliminarlo.',
                            409
                        );
                    }
                }
            }

            $internalName = basename((string) $videoRow['archivo_interno']);
            $this->db()->prepare('DELETE FROM recorrido_video WHERE id_video = ?')->execute([$videoId]);
            $this->db()->commit();
        } catch (Throwable $e) {
            if ($this->db()->inTransaction()) {
                $this->db()->rollBack();
            }
            throw $e;
        }

        if ($internalName !== null) {
            $path = $this->videosDir . DIRECTORY_SEPARATOR . $internalName;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Extrae una imagen desde un momento del video (FFmpeg primario o captura validada del navegador)
     * y la registra en el almacén unificado recorrido_archivo devolviendo una escena del contrato.
     */
    public function extractScene(int $propertyId, int $userId, array $post, array $files): array {
        $property = $this->photoModel->owner($propertyId, $userId);
        if ($property['fecha_retiro'] !== null) {
            throw new RuntimeException('No puedes extraer escenas en un anuncio retirado.', 400);
        }

        $this->ensureDirectories();

        $videoIdRaw = filter_var($post['video_id'] ?? null, FILTER_VALIDATE_INT);
        if ($videoIdRaw === false || (int) $videoIdRaw <= 0) {
            throw new RuntimeException('Selecciona un video válido para extraer la escena.', 400);
        }
        $videoId = (int) $videoIdRaw;

        $vidStmt = $this->db()->prepare(
            "SELECT * FROM recorrido_video
             WHERE id_video = ? AND id_propiedad = ? AND id_propietario = ? AND estado = 'LISTO'
             LIMIT 1"
        );
        $vidStmt->execute([$videoId, $propertyId, $userId]);
        $video = $vidStmt->fetch();
        if (!$video) {
            throw new RuntimeException('El video seleccionado no existe o no pertenece a esta propiedad.', 404);
        }

        $videoPath = $this->videosDir . DIRECTORY_SEPARATOR . basename((string) $video['archivo_interno']);
        if (!is_file($videoPath)) {
            throw new RuntimeException('El archivo de video original ya no está disponible.', 404);
        }

        $nombre = trim((string) ($post['nombre'] ?? ''));
        if (mb_strlen($nombre) < 1 || mb_strlen($nombre) > 80) {
            throw new RuntimeException('Asigna un nombre a la escena (entre 1 y 80 caracteres).', 400);
        }

        $momentoRaw = filter_var($post['momento_segundos'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($momentoRaw === false || !is_finite((float) $momentoRaw) || (float) $momentoRaw < 0) {
            throw new RuntimeException('El momento seleccionado en el video es inválido.', 400);
        }
        $momento = round((float) $momentoRaw, 2);

        [$inicio, $fin] = $this->validateFragmentRange(
            $post['inicio_segundos'] ?? null,
            $post['fin_segundos'] ?? null
        );

        $videoDuration = $video['duracion_segundos'] !== null ? (float) $video['duracion_segundos'] : null;
        if (($videoDuration === null || $videoDuration <= 0) && isset($post['duracion_segundos'])) {
            $clientDur = $this->sanitizeOptionalDuration(
                is_numeric($post['duracion_segundos']) ? (float) $post['duracion_segundos'] : null
            );
            if ($clientDur !== null) {
                $videoDuration = $clientDur;
                $this->db()->prepare('UPDATE recorrido_video SET duracion_segundos = ? WHERE id_video = ?')
                    ->execute([$clientDur, $videoId]);
            }
        }

        if ($videoDuration !== null && $videoDuration > 0) {
            $limit = $videoDuration + 0.5;
            if ($momento > $limit || ($inicio !== null && $inicio > $limit) || ($fin !== null && $fin > $limit)) {
                throw new RuntimeException('El momento o fragmento seleccionado excede la duración del video.', 400);
            }
        }

        $mode = strtolower(trim((string) ($post['modo_extraccion'] ?? 'auto')));
        if (!in_array($mode, ['auto', 'ffmpeg', 'navegador'], true)) {
            throw new RuntimeException('Modo de extracción no válido.', 400);
        }

        $frameFile = $files['frame'] ?? ($files['frame_image'] ?? []);
        $hasUploadedFrame = is_array($frameFile)
            && (($frameFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

        $useFfmpeg = ($mode === 'ffmpeg') || ($mode === 'auto' && !$hasUploadedFrame);

        $tempPath = null;
        $destPath = null;

        try {
            if ($useFfmpeg) {
                $tempName = 'tmp_' . $propertyId . '_' . bin2hex(random_bytes(8)) . '.jpg';
                $tempPath = $this->tmpDir . DIRECTORY_SEPARATOR . $tempName;

                $res = VideoProcessor::extractFrame($videoPath, $momento, $tempPath);
                if (empty($res['ok'])) {
                    $code = ($res['code'] ?? '') === 'ffmpeg_unavailable' ? 503 : 422;
                    throw new RuntimeException((string) ($res['message'] ?? 'No se pudo extraer el fotograma con FFmpeg.'), $code);
                }

                [$mime, $ext, $width, $height] = $this->validateLocalFrameImage($tempPath, 422);
                $origin = 'ffmpeg';
            } else {
                [$tmpUploaded, $mime, $ext, $width, $height] = $this->validateUploadedFrame($frameFile);
                $origin = 'navegador';
            }

            $fileName = bin2hex(random_bytes(20)) . '.' . $ext;
            $destPath = $this->scenesDir . DIRECTORY_SEPARATOR . $fileName;
            $moved = false;

            $this->db()->beginTransaction();
            $tourId = $this->photoModel->ensureTour($propertyId);

            $countStmt = $this->db()->prepare('SELECT COUNT(*) FROM recorrido_archivo WHERE id_recorrido = ? FOR UPDATE');
            $countStmt->execute([$tourId]);
            if ((int) $countStmt->fetchColumn() >= self::MAX_FILES_PER_TOUR) {
                throw new RuntimeException(
                    'Guarda o publica los cambios para liberar imágenes sin usar. Límite: ' . self::MAX_FILES_PER_TOUR . ' archivos por recorrido.',
                    400
                );
            }

            if ($useFfmpeg) {
                $moved = @rename($tempPath, $destPath);
            } else {
                $moved = move_uploaded_file($tmpUploaded, $destPath);
            }
            if (!$moved) {
                throw new RuntimeException('No se pudo guardar la imagen de la escena.', 503);
            }

            $this->db()->prepare(
                'INSERT INTO recorrido_archivo (id_recorrido, ruta, mime, ancho, alto) VALUES (?, ?, ?, ?, ?)'
            )->execute([$tourId, $fileName, $mime, $width, $height]);
            $archiveId = (int) $this->db()->lastInsertId();
            $this->db()->commit();

            return [
                'origen_extraccion' => $origin,
                'scene'             => [
                    'id'                => $archiveId,
                    'nombre'            => $nombre,
                    'tipo_visual'       => 'imagen',
                    'imagen_url'        => BASE_URL . 'recorridos/imagen/' . $archiveId,
                    'ancho'             => $width,
                    'alto'              => $height,
                    'angulo_horizontal' => 180,
                    'video_id'          => $videoId,
                    'inicio_segundos'   => $inicio,
                    'fin_segundos'      => $fin,
                ],
            ];
        } catch (Throwable $e) {
            if ($this->db()->inTransaction()) {
                $this->db()->rollBack();
            }
            if ($destPath !== null && is_file($destPath)) {
                @unlink($destPath);
            }
            throw $e;
        } finally {
            if ($tempPath !== null && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Autoriza y entrega la ruta del video asociado a una escena dentro del recorrido publicado
     * (o del borrador cuando el propietario autenticado solicita vista previa).
     */
    public function getAuthorizedSceneVideoFile(int $propertyId, int $sceneId, ?int $userId, bool $preview): array {
        if ($preview && ($userId === null || $userId <= 0)) {
            throw new RuntimeException('Inicia sesión para previsualizar el borrador.', 401);
        }

        $tourData = $this->photoModel->load($propertyId, $preview ? $userId : null);
        $matchedScene = null;
        foreach (($tourData['graph']['escenas'] ?? []) as $scene) {
            if ((int) ($scene['id'] ?? 0) === $sceneId) {
                $matchedScene = $scene;
                break;
            }
        }

        if ($matchedScene === null || empty($matchedScene['video_id'])) {
            throw new RuntimeException('Escena de video no disponible.', 404);
        }

        $videoId = (int) $matchedScene['video_id'];
        $stmt = $this->db()->prepare(
            "SELECT archivo_interno, mime_type
             FROM recorrido_video
             WHERE id_video = ? AND id_propiedad = ? AND estado = 'LISTO'
             LIMIT 1"
        );
        $stmt->execute([$videoId, $propertyId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Video de la escena no disponible.', 404);
        }

        $path = $this->videosDir . DIRECTORY_SEPARATOR . basename((string) $row['archivo_interno']);
        if (!is_file($path)) {
            throw new RuntimeException('Video de la escena no disponible.', 404);
        }

        return [
            'path'      => $path,
            'mime_type' => (string) $row['mime_type'],
        ];
    }

    private function formatVideoRow(int $propertyId, array $row): array {
        $id = (int) $row['id_video'];
        return [
            'id'                => $id,
            'id_video'          => $id,
            'id_recorrido'      => (int) $row['id_recorrido'],
            'id_propiedad'      => (int) $row['id_propiedad'],
            'nombre_original'   => (string) $row['nombre_original'],
            'mime_type'         => (string) $row['mime_type'],
            'tamano_bytes'      => (int) $row['tamano_bytes'],
            'duracion_segundos' => $row['duracion_segundos'] !== null ? (float) $row['duracion_segundos'] : null,
            'ancho'             => $row['ancho'] !== null ? (int) $row['ancho'] : null,
            'alto'              => $row['alto'] !== null ? (int) $row['alto'] : null,
            'fecha_subida'      => (string) $row['fecha_subida'],
            'stream_url'        => BASE_URL . 'api/mis-propiedades/' . $propertyId . '/recorrido-video/videos/' . $id . '/stream',
        ];
    }

    private function validateFragmentRange($rawStart, $rawEnd): array {
        $start = null;
        $end = null;

        if ($rawStart !== null && $rawStart !== '') {
            $val = filter_var($rawStart, FILTER_VALIDATE_FLOAT);
            if ($val === false || !is_finite((float) $val) || (float) $val < 0) {
                throw new RuntimeException('El segundo inicial del fragmento es inválido.', 400);
            }
            $start = round((float) $val, 2);
        }

        if ($rawEnd !== null && $rawEnd !== '') {
            $val = filter_var($rawEnd, FILTER_VALIDATE_FLOAT);
            if ($val === false || !is_finite((float) $val) || (float) $val <= 0) {
                throw new RuntimeException('El segundo final del fragmento es inválido.', 400);
            }
            $end = round((float) $val, 2);
        }

        if ($end !== null && $start === null) {
            throw new RuntimeException('Define el inicio del fragmento antes de indicar el final.', 400);
        }
        if ($start !== null && $end !== null && $end <= $start) {
            throw new RuntimeException('El segundo final debe ser mayor que el inicial.', 400);
        }

        return [$start, $end];
    }

    private function validateUploadedFrame($file): array {
        if (!is_array($file)
            || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || empty($file['tmp_name'])
            || !is_uploaded_file((string) $file['tmp_name'])
        ) {
            throw new RuntimeException(
                'No se recibió el fotograma de la escena y FFmpeg no está habilitado. Captura el fotograma desde el reproductor.',
                400
            );
        }

        $tmp = (string) $file['tmp_name'];
        $sizeBytes = (int) ($file['size'] ?? 0);
        if ($sizeBytes <= 0 || $sizeBytes > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('La imagen del fotograma excede el máximo de 8 MB o está vacía.', 400);
        }

        [$mime, $ext, $width, $height] = $this->validateLocalFrameImage($tmp, 400);
        return [$tmp, $mime, $ext, $width, $height];
    }

    private function validateLocalFrameImage(string $path, int $errorCode): array {
        $sizeBytes = is_file($path) ? (int) filesize($path) : 0;
        if ($sizeBytes <= 0 || $sizeBytes > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('La imagen extraída excede el máximo de 8 MB o está vacía.', $errorCode);
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string) finfo_file($finfo, $path) : (string) (@mime_content_type($path) ?: '');
        if ($finfo) {
            finfo_close($finfo);
        }

        $ext = self::ALLOWED_IMAGE_MIMES[$mime] ?? null;
        $info = @getimagesize($path);
        if (
            $ext === null
            || $info === false
            || $info[0] < 100
            || $info[1] < 100
            || $info[0] > 24000
            || $info[1] > 24000
            || ($info[0] * $info[1]) > 64000000
        ) {
            throw new RuntimeException(
                'El fotograma debe ser JPG, PNG o WEBP de al menos 100 × 100 y máximo 64 megapíxeles.',
                $errorCode
            );
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path, 'IFD0');
            if (in_array((int) ($exif['Orientation'] ?? 1), [5, 6, 7, 8], true)) {
                [$width, $height] = [$height, $width];
            }
        }

        return [$mime, $ext, $width, $height];
    }

    private function sanitizeOptionalDuration(?float $dur): ?float {
        if ($dur === null || !is_finite($dur) || $dur <= 0 || $dur > self::MAX_DURATION_SECONDS) {
            return null;
        }
        return round($dur, 2);
    }

    private function sanitizeOptionalDimension(?int $dim): ?int {
        if ($dim === null || $dim < 16 || $dim > 7680) {
            return null;
        }
        return $dim;
    }
}

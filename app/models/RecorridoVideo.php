<?php
/**
 * Modelo RecorridoVideo
 * Gestiona recorridos por escenas, carga segura de videos privados,
 * extracción de fotogramas y conexiones interactivas entre escenas.
 * INFONATEC Inmobiliaria
 */

class RecorridoVideo extends Model {
    public const MAX_DURATION_SECONDS = 600.0; // 10 minutos
    public const MAX_SCENES_PER_TOUR = 30;
    public const MAX_VIDEOS_PER_PROPERTY = 5;
    public const MAX_IMAGE_BYTES = 5242880; // 5 MB
    public const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private string $rootDir;
    private string $videosDir;
    private string $tmpDir;
    private string $scenesPublicDir;

    public function __construct() {
        parent::__construct();
        $this->rootDir = dirname(__DIR__, 2);
        $this->videosDir = $this->rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'recorridos_videos';
        $this->tmpDir = $this->rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'recorridos_tmp';
        $this->scenesPublicDir = $this->rootDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'recorridos' . DIRECTORY_SEPARATOR . 'escenas';
    }

    private function ensureDirectories(): void {
        foreach ([$this->videosDir, $this->tmpDir, $this->scenesPublicDir] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('No se pudo preparar el directorio de almacenamiento.');
            }
        }
    }

    /**
     * Comprueba que las tablas de recorridos por escenas existan tras migración.
     */
    public function tablesReady(): bool {
        if ($this->db === null) {
            return false;
        }
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME IN ('recorrido', 'recorrido_video', 'recorrido_escena', 'recorrido_conexion')"
            );
            return (int) $stmt->fetchColumn() === 4;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Verifica en base de datos que la propiedad pertenezca al usuario autenticado.
     * Nunca confía en datos enviados por el cliente.
     */
    public function assertOwnership(int $ownerId, int $propertyId, bool $requireEditable = false): ?array {
        if ($this->db === null || $ownerId <= 0 || $propertyId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT p.id_propiedad, p.id_propietario, p.titulo, p.ubicacion, p.operacion, p.fecha_retiro,
                    e.nombre AS estado, c.nombre AS categoria
             FROM propiedad p
             INNER JOIN estado_propiedad e ON e.id_estado = p.id_estado
             INNER JOIN categoria c ON c.id_categoria = p.id_categoria
             WHERE p.id_propiedad = ? AND p.id_propietario = ?
             LIMIT 1"
        );
        $stmt->execute([$propertyId, $ownerId]);
        $property = $stmt->fetch();

        if (!$property) {
            return null;
        }
        if ($requireEditable && ($property['fecha_retiro'] !== null || $property['estado'] !== 'DISPONIBLE')) {
            throw new RuntimeException('Esta propiedad ya no está disponible para editar su recorrido.');
        }

        return $property;
    }

    /**
     * Obtiene o crea el recorrido asociado a la propiedad del dueño autenticado.
     */
    public function getOrCreateTour(int $ownerId, int $propertyId): array {
        $property = $this->assertOwnership($ownerId, $propertyId, false);
        if (!$property) {
            throw new RuntimeException('La publicación no existe o no pertenece a tu cuenta.');
        }
        if (!$this->tablesReady()) {
            throw new RuntimeException('Falta ejecutar la migración database/migrations/20261126_recorridos_video.sql.');
        }

        $stmt = $this->db->prepare('SELECT * FROM recorrido WHERE id_propiedad = ? LIMIT 1');
        $stmt->execute([$propertyId]);
        $tour = $stmt->fetch();

        if (!$tour) {
            $title = 'Recorrido interactivo — ' . mb_substr((string) $property['titulo'], 0, 140);
            $ins = $this->db->prepare(
                "INSERT INTO recorrido (id_propiedad, titulo, descripcion, estado)
                 VALUES (?, ?, ?, 'BORRADOR')"
            );
            $ins->execute([
                $propertyId,
                $title,
                'Recorrido por escenas de ' . mb_substr((string) $property['titulo'], 0, 180),
            ]);
            $stmt->execute([$propertyId]);
            $tour = $stmt->fetch();
        }

        return $tour;
    }

    /**
     * Carga el paquete completo para el estudio del propietario.
     */
    public function getOwnerTourBundle(int $ownerId, int $propertyId): ?array {
        $property = $this->assertOwnership($ownerId, $propertyId, false);
        if (!$property) {
            return null;
        }
        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        $videosStmt = $this->db->prepare(
            "SELECT id_video, id_recorrido, id_propiedad, nombre_original, mime_type, tamano_bytes,
                    duracion_segundos, ancho, alto, estado, mensaje_error, fecha_subida
             FROM recorrido_video
             WHERE id_recorrido = ? AND estado <> 'ELIMINADO'
             ORDER BY id_video DESC"
        );
        $videosStmt->execute([$tourId]);
        $videos = array_map(
            fn(array $row): array => $this->formatVideo($row, $propertyId),
            $videosStmt->fetchAll()
        );

        $scenes = $this->fetchScenes($tourId, false);
        $connections = $this->fetchConnections($tourId);

        return [
            'success'      => true,
            'contract'     => 'infonatec-tour-v1',
            'propiedad'    => [
                'id'        => (int) $property['id_propiedad'],
                'titulo'    => (string) $property['titulo'],
                'ubicacion' => (string) $property['ubicacion'],
                'estado'    => (string) $property['estado'],
                'editable'  => $property['fecha_retiro'] === null && $property['estado'] === 'DISPONIBLE',
            ],
            'recorrido'    => $this->formatTour($tour),
            'videos'       => $videos,
            'escenas'      => $scenes,
            'conexiones'   => $connections,
            'procesador'   => VideoProcessor::getStatus(),
        ];
    }

    /**
     * Obtiene el recorrido en formato del contrato común para el visor visitante o vista previa del dueño.
     */
    public function getPublicTourBundle(int $propertyId, ?int $viewerUserId = null): ?array {
        if ($this->db === null || $propertyId <= 0 || !$this->tablesReady()) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT r.*, p.id_propietario, p.titulo AS propiedad_titulo, p.ubicacion, p.fecha_retiro, e.nombre AS propiedad_estado
             FROM recorrido r
             INNER JOIN propiedad p ON p.id_propiedad = r.id_propiedad
             INNER JOIN estado_propiedad e ON e.id_estado = p.id_estado
             WHERE r.id_propiedad = ?
             LIMIT 1"
        );
        $stmt->execute([$propertyId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $isOwner = $viewerUserId !== null && $viewerUserId > 0 && (int) $row['id_propietario'] === $viewerUserId;
        $isPublic = $row['fecha_retiro'] === null
            && $row['propiedad_estado'] === 'DISPONIBLE'
            && $row['estado'] === 'PUBLICADO';

        if (!$isPublic && !$isOwner) {
            return null;
        }

        $tourId = (int) $row['id_recorrido'];
        $scenes = $this->fetchScenes($tourId, true);
        if (empty($scenes) && !$isOwner) {
            return null;
        }

        $connections = $this->fetchConnections($tourId);

        return [
            'success'    => true,
            'contract'   => 'infonatec-tour-v1',
            'is_preview' => !$isPublic && $isOwner,
            'propiedad'  => [
                'id'        => (int) $row['id_propiedad'],
                'titulo'    => (string) $row['propiedad_titulo'],
                'ubicacion' => (string) $row['ubicacion'],
            ],
            'recorrido'  => $this->formatTour($row),
            'escenas'    => $scenes,
            'conexiones' => $connections,
        ];
    }

    /**
     * Valida y guarda un video convencional del propietario en storage/recorridos_videos/ (no público).
     */
    public function saveUploadedVideo(
        int $ownerId,
        int $propertyId,
        ?array $file,
        ?float $clientDuration = null,
        ?int $clientWidth = null,
        ?int $clientHeight = null
    ): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        if (!is_array($file) || !isset($file['error'])) {
            return ['success' => false, 'code' => 422, 'message' => 'Selecciona un archivo de video válido (MP4, MOV o WebM).'];
        }

        $err = (int) $file['error'];
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            $maxMb = round(VideoProcessor::getEffectiveMaxVideoBytes() / 1048576, 1);
            return ['success' => false, 'code' => 413, 'message' => "El video excede el límite máximo permitido ({$maxMb} MB)."];
        }
        if ($err === UPLOAD_ERR_PARTIAL) {
            return ['success' => false, 'code' => 400, 'message' => 'La carga del video fue interrumpida. Inténtalo nuevamente.'];
        }
        if ($err !== UPLOAD_ERR_OK) {
            return ['success' => false, 'code' => 422, 'message' => 'No se recibió el archivo de video completo.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $maxBytes = VideoProcessor::getEffectiveMaxVideoBytes();

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'code' => 422, 'message' => 'Archivo de video inválido.'];
        }
        if ($size <= 0 || $size > $maxBytes) {
            $maxMb = round($maxBytes / 1048576, 1);
            return ['success' => false, 'code' => 413, 'message' => "El video debe pesar más de 0 bytes y como máximo {$maxMb} MB."];
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!isset(VideoProcessor::ALLOWED_VIDEO_MIMES[$mime]) || !VideoProcessor::validateVideoHeader($tmpName, $mime)) {
            return ['success' => false, 'code' => 422, 'message' => 'Formato de video no permitido o archivo dañado. Usa únicamente MP4, MOV o WebM válidos.'];
        }

        $ext = VideoProcessor::ALLOWED_VIDEO_MIMES[$mime];
        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM recorrido_video WHERE id_recorrido = ? AND estado <> 'ELIMINADO'");
        $countStmt->execute([$tourId]);
        if ((int) $countStmt->fetchColumn() >= self::MAX_VIDEOS_PER_PROPERTY) {
            return ['success' => false, 'code' => 422, 'message' => 'Puedes conservar hasta ' . self::MAX_VIDEOS_PER_PROPERTY . ' videos por propiedad. Elimina uno antes de subir otro.'];
        }

        $this->ensureDirectories();
        $serverFilename = 'vid_' . $propertyId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $this->videosDir . DIRECTORY_SEPARATOR . $serverFilename;

        if (!move_uploaded_file($tmpName, $destPath)) {
            return ['success' => false, 'code' => 500, 'message' => 'No se pudo almacenar el video en el servidor.'];
        }

        try {
            $probe = VideoProcessor::probeVideo($destPath);
            if ($probe['probed'] && !$probe['valid']) {
                @unlink($destPath);
                return ['success' => false, 'code' => 422, 'message' => $probe['message'] ?? 'El video no contiene una pista válida.'];
            }

            $duration = $probe['duration'] ?? (($clientDuration !== null && is_finite($clientDuration) && $clientDuration > 0) ? round($clientDuration, 2) : null);
            $width = $probe['width'] ?? (($clientWidth !== null && $clientWidth > 0 && $clientWidth <= 7680) ? $clientWidth : null);
            $height = $probe['height'] ?? (($clientHeight !== null && $clientHeight > 0 && $clientHeight <= 4320) ? $clientHeight : null);

            if ($duration !== null && $duration > self::MAX_DURATION_SECONDS) {
                @unlink($destPath);
                return ['success' => false, 'code' => 422, 'message' => 'El video supera el máximo permitido de 10 minutos.'];
            }

            $originalName = $this->sanitizeDisplayFilename((string) ($file['name'] ?? 'video.' . $ext));

            $this->db->beginTransaction();
            $ins = $this->db->prepare(
                "INSERT INTO recorrido_video
                    (id_recorrido, id_propiedad, id_propietario, nombre_original, archivo_interno, mime_type, tamano_bytes, duracion_segundos, ancho, alto, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'LISTO')"
            );
            $ins->execute([
                $tourId,
                $propertyId,
                $ownerId,
                $originalName,
                $serverFilename,
                $mime,
                $size,
                $duration,
                $width,
                $height,
            ]);
            $videoId = (int) $this->db->lastInsertId();
            $this->db->commit();

            $rowStmt = $this->db->prepare('SELECT * FROM recorrido_video WHERE id_video = ? LIMIT 1');
            $rowStmt->execute([$videoId]);
            $videoRow = $rowStmt->fetch();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Video cargado y protegido correctamente.',
                'video'   => $this->formatVideo($videoRow, $propertyId),
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            @unlink($destPath);
            error_log('Error guardando video de recorrido: ' . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'No fue posible registrar el video en la base de datos.'];
        }
    }

    /**
     * Resuelve un video del propietario validando titularidad para streaming autenticado.
     */
    public function getOwnerVideoForStreaming(int $ownerId, int $propertyId, int $videoId): ?array {
        if ($this->db === null || $ownerId <= 0 || $propertyId <= 0 || $videoId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT rv.id_video, rv.archivo_interno, rv.mime_type, rv.tamano_bytes
             FROM recorrido_video rv
             INNER JOIN propiedad p ON p.id_propiedad = rv.id_propiedad
             WHERE rv.id_video = ? AND rv.id_propiedad = ? AND p.id_propietario = ? AND rv.estado <> 'ELIMINADO'
             LIMIT 1"
        );
        $stmt->execute([$videoId, $propertyId, $ownerId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $path = $this->safeVideoPath((string) $row['archivo_interno']);
        if ($path === null || !is_file($path)) {
            return null;
        }

        return [
            'path'      => $path,
            'mime_type' => (string) $row['mime_type'],
        ];
    }

    /**
     * Elimina un video del propietario; las escenas ya extraídas conservan su imagen con video_id = NULL.
     */
    public function deleteVideo(int $ownerId, int $propertyId, int $videoId): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        $stmt = $this->db->prepare(
            "SELECT rv.id_video, rv.archivo_interno
             FROM recorrido_video rv
             INNER JOIN propiedad p ON p.id_propiedad = rv.id_propiedad
             WHERE rv.id_video = ? AND rv.id_propiedad = ? AND p.id_propietario = ?
             LIMIT 1"
        );
        $stmt->execute([$videoId, $propertyId, $ownerId]);
        $video = $stmt->fetch();
        if (!$video) {
            return ['success' => false, 'code' => 404, 'message' => 'El video no existe o no pertenece a tu propiedad.'];
        }

        $filePath = $this->safeVideoPath((string) $video['archivo_interno']);

        try {
            $this->db->beginTransaction();
            $this->db->prepare('UPDATE recorrido_escena SET video_id = NULL WHERE video_id = ?')->execute([$videoId]);
            $this->db->prepare('DELETE FROM recorrido_video WHERE id_video = ? AND id_propiedad = ?')->execute([$videoId, $propertyId]);
            $this->db->commit();

            if ($filePath !== null && is_file($filePath)) {
                @unlink($filePath);
            }

            return ['success' => true, 'code' => 200, 'message' => 'Video eliminado. Las imágenes de escenas ya extraídas se conservaron.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error eliminando video: ' . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'No se pudo eliminar el video.'];
        }
    }

    /**
     * Extrae y guarda una escena plana ('imagen') desde un video del propietario.
     */
    public function createScene(int $ownerId, int $propertyId, array $data, ?array $capturedFrameFile = null): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM recorrido_escena WHERE id_recorrido = ?');
        $countStmt->execute([$tourId]);
        if ((int) $countStmt->fetchColumn() >= self::MAX_SCENES_PER_TOUR) {
            return ['success' => false, 'code' => 422, 'message' => 'Has alcanzado el límite de ' . self::MAX_SCENES_PER_TOUR . ' escenas para este recorrido.'];
        }

        $nombre = trim((string) ($data['nombre'] ?? ''));
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 120) {
            return ['success' => false, 'code' => 422, 'message' => 'Asigna un nombre válido a la escena (2 a 120 caracteres), por ejemplo Sala o Cocina.'];
        }

        $videoId = filter_var($data['video_id'] ?? null, FILTER_VALIDATE_INT);
        if ($videoId === false || (int) $videoId <= 0) {
            return ['success' => false, 'code' => 422, 'message' => 'Selecciona un video válido de esta propiedad.'];
        }

        $video = $this->findAuthorizedVideo($tourId, $propertyId, (int) $videoId);
        if (!$video) {
            return ['success' => false, 'code' => 404, 'message' => 'El video seleccionado no pertenece a esta propiedad.'];
        }

        $bounds = $this->validateSceneTiming($data, $video['duracion_segundos'] !== null ? (float) $video['duracion_segundos'] : null, true);
        if (!$bounds['ok']) {
            return ['success' => false, 'code' => 422, 'message' => $bounds['message']];
        }

        $mode = strtolower(trim((string) ($data['modo_extraccion'] ?? 'auto')));
        $extracted = $this->resolveAndPersistSceneImage(
            $tourId,
            $video,
            $bounds['momento'],
            $mode,
            $capturedFrameFile
        );
        if (!$extracted['ok']) {
            return ['success' => false, 'code' => $extracted['code'], 'message' => $extracted['message']];
        }

        $savedPublicPath = $extracted['full_path'];
        $relativeUrl = $extracted['relative_url'];

        try {
            $this->db->beginTransaction();

            $orderStmt = $this->db->prepare('SELECT COALESCE(MAX(orden), 0) + 1 FROM recorrido_escena WHERE id_recorrido = ?');
            $orderStmt->execute([$tourId]);
            $nextOrder = (int) $orderStmt->fetchColumn();

            $ins = $this->db->prepare(
                "INSERT INTO recorrido_escena
                    (id_recorrido, nombre, tipo_visual, imagen_url, video_id, momento_segundos, inicio_segundos, fin_segundos, orden, origen_extraccion)
                 VALUES (?, ?, 'imagen', ?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->execute([
                $tourId,
                $nombre,
                $relativeUrl,
                (int) $videoId,
                $bounds['momento'],
                $bounds['inicio'],
                $bounds['fin'],
                $nextOrder,
                $extracted['origen'],
            ]);
            $sceneId = (int) $this->db->lastInsertId();

            if (empty($tour['escena_inicial_id'])) {
                $this->db->prepare('UPDATE recorrido SET escena_inicial_id = ? WHERE id_recorrido = ?')
                    ->execute([$sceneId, $tourId]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Escena "' . $nombre . '" guardada correctamente.',
                'escena'  => $this->fetchSingleScene($tourId, $sceneId, false),
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (is_file($savedPublicPath)) {
                @unlink($savedPublicPath);
            }
            error_log('Error creando escena de video: ' . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'No se pudo guardar la escena en la base de datos.'];
        }
    }

    /**
     * Actualiza nombre, límites de fragmento, orden o re-extrae la imagen de una escena existente.
     */
    public function updateScene(int $ownerId, int $propertyId, int $sceneId, array $data, ?array $capturedFrameFile = null): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        $sceneStmt = $this->db->prepare('SELECT * FROM recorrido_escena WHERE id_escena = ? AND id_recorrido = ? LIMIT 1');
        $sceneStmt->execute([$sceneId, $tourId]);
        $existing = $sceneStmt->fetch();
        if (!$existing) {
            return ['success' => false, 'code' => 404, 'message' => 'La escena no existe o no pertenece a este recorrido.'];
        }

        $nombre = array_key_exists('nombre', $data) ? trim((string) $data['nombre']) : (string) $existing['nombre'];
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 120) {
            return ['success' => false, 'code' => 422, 'message' => 'El nombre de la escena debe tener entre 2 y 120 caracteres.'];
        }

        $orden = (int) $existing['orden'];
        if (array_key_exists('orden', $data) && $data['orden'] !== '' && $data['orden'] !== null) {
            $parsedOrder = filter_var($data['orden'], FILTER_VALIDATE_INT);
            if ($parsedOrder === false || $parsedOrder < 1 || $parsedOrder > 999) {
                return ['success' => false, 'code' => 422, 'message' => 'El orden debe ser un entero entre 1 y 999.'];
            }
            $orden = (int) $parsedOrder;
        }

        $video = !empty($existing['video_id'])
            ? $this->findAuthorizedVideo($tourId, $propertyId, (int) $existing['video_id'])
            : null;
        $maxDuration = ($video && $video['duracion_segundos'] !== null) ? (float) $video['duracion_segundos'] : null;

        $timingInput = [
            'momento_segundos' => array_key_exists('momento_segundos', $data) ? $data['momento_segundos'] : $existing['momento_segundos'],
            'inicio_segundos'  => array_key_exists('inicio_segundos', $data) ? $data['inicio_segundos'] : $existing['inicio_segundos'],
            'fin_segundos'     => array_key_exists('fin_segundos', $data) ? $data['fin_segundos'] : $existing['fin_segundos'],
        ];
        $bounds = $this->validateSceneTiming($timingInput, $maxDuration, false);
        if (!$bounds['ok']) {
            return ['success' => false, 'code' => 422, 'message' => $bounds['message']];
        }

        $reExtract = !empty($data['reextraer_imagen']) && (string) $data['reextraer_imagen'] === '1';
        $newRelativeUrl = (string) $existing['imagen_url'];
        $newOrigen = (string) $existing['origen_extraccion'];
        $newFullPath = null;
        $oldRelativeUrl = (string) $existing['imagen_url'];

        if ($reExtract || ($capturedFrameFile !== null && ($capturedFrameFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
            if (!$video) {
                return ['success' => false, 'code' => 422, 'message' => 'El video de origen ya no está disponible para volver a extraer la imagen.'];
            }
            if ($bounds['momento'] === null) {
                return ['success' => false, 'code' => 422, 'message' => 'Indica el segundo del video para extraer la nueva imagen.'];
            }
            $mode = strtolower(trim((string) ($data['modo_extraccion'] ?? 'auto')));
            $extracted = $this->resolveAndPersistSceneImage($tourId, $video, $bounds['momento'], $mode, $capturedFrameFile);
            if (!$extracted['ok']) {
                return ['success' => false, 'code' => $extracted['code'], 'message' => $extracted['message']];
            }
            $newRelativeUrl = $extracted['relative_url'];
            $newOrigen = $extracted['origen'];
            $newFullPath = $extracted['full_path'];
        }

        try {
            $this->db->beginTransaction();
            $upd = $this->db->prepare(
                "UPDATE recorrido_escena
                 SET nombre = ?, imagen_url = ?, momento_segundos = ?, inicio_segundos = ?, fin_segundos = ?, orden = ?, origen_extraccion = ?
                 WHERE id_escena = ? AND id_recorrido = ?"
            );
            $upd->execute([
                $nombre,
                $newRelativeUrl,
                $bounds['momento'],
                $bounds['inicio'],
                $bounds['fin'],
                $orden,
                $newOrigen,
                $sceneId,
                $tourId,
            ]);
            $this->db->commit();

            if ($newFullPath !== null && $oldRelativeUrl !== $newRelativeUrl) {
                $this->deleteSceneImageFile($oldRelativeUrl);
            }

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Escena actualizada correctamente.',
                'escena'  => $this->fetchSingleScene($tourId, $sceneId, false),
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($newFullPath !== null && is_file($newFullPath)) {
                @unlink($newFullPath);
            }
            error_log('Error actualizando escena: ' . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'No se pudo actualizar la escena.'];
        }
    }

    /**
     * Elimina una escena del recorrido autorizado y su archivo de imagen si pertenece a escenas/.
     */
    public function deleteScene(int $ownerId, int $propertyId, int $sceneId): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        $sceneStmt = $this->db->prepare('SELECT * FROM recorrido_escena WHERE id_escena = ? AND id_recorrido = ? LIMIT 1');
        $sceneStmt->execute([$sceneId, $tourId]);
        $scene = $sceneStmt->fetch();
        if (!$scene) {
            return ['success' => false, 'code' => 404, 'message' => 'La escena no existe o no pertenece a este recorrido.'];
        }

        try {
            $this->db->beginTransaction();

            $this->db->prepare('DELETE FROM recorrido_escena WHERE id_escena = ? AND id_recorrido = ?')
                ->execute([$sceneId, $tourId]);

            if ((int) ($tour['escena_inicial_id'] ?? 0) === $sceneId) {
                $nextStmt = $this->db->prepare('SELECT id_escena FROM recorrido_escena WHERE id_recorrido = ? ORDER BY orden ASC, id_escena ASC LIMIT 1');
                $nextStmt->execute([$tourId]);
                $nextId = $nextStmt->fetchColumn();
                $this->db->prepare('UPDATE recorrido SET escena_inicial_id = ? WHERE id_recorrido = ?')
                    ->execute([$nextId ? (int) $nextId : null, $tourId]);
            }

            $remainingStmt = $this->db->prepare('SELECT COUNT(*) FROM recorrido_escena WHERE id_recorrido = ?');
            $remainingStmt->execute([$tourId]);
            if ((int) $remainingStmt->fetchColumn() === 0 && $tour['estado'] === 'PUBLICADO') {
                $this->db->prepare("UPDATE recorrido SET estado = 'BORRADOR' WHERE id_recorrido = ?")->execute([$tourId]);
            }

            $this->db->commit();
            $this->deleteSceneImageFile((string) $scene['imagen_url']);

            return ['success' => true, 'code' => 200, 'message' => 'Escena eliminada correctamente.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error eliminando escena: ' . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'No se pudo eliminar la escena.'];
        }
    }

    /**
     * Crea una conexión entre dos escenas del mismo recorrido autorizado.
     */
    public function createConnection(int $ownerId, int $propertyId, array $data): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        $originId = (int) ($data['escena_origen_id'] ?? 0);
        $targetId = (int) ($data['escena_destino_id'] ?? 0);
        $label = trim((string) ($data['etiqueta'] ?? ''));

        if ($originId <= 0 || $targetId <= 0 || $originId === $targetId) {
            return ['success' => false, 'code' => 422, 'message' => 'Selecciona una escena de origen y una de destino distintas.'];
        }
        if (mb_strlen($label) < 2 || mb_strlen($label) > 120) {
            return ['success' => false, 'code' => 422, 'message' => 'La etiqueta de la conexión debe tener entre 2 y 120 caracteres.'];
        }

        $checkStmt = $this->db->prepare('SELECT id_escena FROM recorrido_escena WHERE id_recorrido = ? AND id_escena IN (?, ?)');
        $checkStmt->execute([$tourId, $originId, $targetId]);
        $found = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($found) !== 2) {
            return ['success' => false, 'code' => 404, 'message' => 'Ambas escenas deben pertenecer al recorrido autorizado.'];
        }

        $posResult = $this->validatePositionPayload($data['posicion'] ?? null, $data);
        if (!$posResult['ok']) {
            return ['success' => false, 'code' => 422, 'message' => $posResult['message']];
        }

        try {
            $ins = $this->db->prepare(
                'INSERT INTO recorrido_conexion (id_recorrido, escena_origen_id, escena_destino_id, etiqueta, posicion_sistema, posicion_json)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $tourId,
                $originId,
                $targetId,
                $label,
                $posResult['sistema'],
                json_encode($posResult['posicion'], JSON_UNESCAPED_UNICODE),
            ]);
            $connId = (int) $this->db->lastInsertId();

            $rowStmt = $this->db->prepare('SELECT * FROM recorrido_conexion WHERE id_conexion = ? LIMIT 1');
            $rowStmt->execute([$connId]);
            $connRow = $rowStmt->fetch();

            return [
                'success'  => true,
                'code'     => 200,
                'message'  => 'Conexión agregada correctamente.',
                'conexion' => $this->formatConnection($connRow),
            ];
        } catch (Throwable $e) {
            error_log('Error creando conexión de recorrido: ' . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'No se pudo guardar la conexión entre escenas.'];
        }
    }

    /**
     * Elimina una conexión perteneciente al recorrido autorizado.
     */
    public function deleteConnection(int $ownerId, int $propertyId, int $connectionId): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        $del = $this->db->prepare('DELETE FROM recorrido_conexion WHERE id_conexion = ? AND id_recorrido = ?');
        $del->execute([$connectionId, $tourId]);
        if ($del->rowCount() === 0) {
            return ['success' => false, 'code' => 404, 'message' => 'La conexión no existe o no pertenece a este recorrido.'];
        }

        return ['success' => true, 'code' => 200, 'message' => 'Conexión eliminada.'];
    }

    /**
     * Cambia el estado del recorrido (BORRADOR / PUBLICADO) validando titularidad y escenas mínimas.
     */
    public function updateTourState(int $ownerId, int $propertyId, string $estado, ?int $initialSceneId = null): array {
        $property = $this->assertOwnership($ownerId, $propertyId, true);
        if (!$property) {
            return ['success' => false, 'code' => 404, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'];
        }

        $estado = strtoupper(trim($estado));
        if (!in_array($estado, ['BORRADOR', 'PUBLICADO'], true)) {
            return ['success' => false, 'code' => 422, 'message' => 'Estado de recorrido no válido.'];
        }

        $tour = $this->getOrCreateTour($ownerId, $propertyId);
        $tourId = (int) $tour['id_recorrido'];

        if ($estado === 'PUBLICADO') {
            $countStmt = $this->db->prepare('SELECT COUNT(*) FROM recorrido_escena WHERE id_recorrido = ?');
            $countStmt->execute([$tourId]);
            if ((int) $countStmt->fetchColumn() < 1) {
                return ['success' => false, 'code' => 422, 'message' => 'Agrega al menos una escena antes de publicar el recorrido.'];
            }
        }

        $escenaInicial = $tour['escena_inicial_id'] !== null ? (int) $tour['escena_inicial_id'] : null;
        if ($initialSceneId !== null && $initialSceneId > 0) {
            $check = $this->db->prepare('SELECT id_escena FROM recorrido_escena WHERE id_escena = ? AND id_recorrido = ? LIMIT 1');
            $check->execute([$initialSceneId, $tourId]);
            if (!$check->fetchColumn()) {
                return ['success' => false, 'code' => 422, 'message' => 'La escena inicial seleccionada no pertenece al recorrido.'];
            }
            $escenaInicial = $initialSceneId;
        }

        $upd = $this->db->prepare('UPDATE recorrido SET estado = ?, escena_inicial_id = ? WHERE id_recorrido = ?');
        $upd->execute([$estado, $escenaInicial, $tourId]);

        $stmt = $this->db->prepare('SELECT * FROM recorrido WHERE id_recorrido = ? LIMIT 1');
        $stmt->execute([$tourId]);
        $updated = $stmt->fetch();

        return [
            'success'   => true,
            'code'      => 200,
            'message'   => $estado === 'PUBLICADO'
                ? 'Recorrido publicado. Los visitantes ya pueden explorar las escenas y fragmentos.'
                : 'Recorrido guardado como borrador.',
            'recorrido' => $this->formatTour($updated),
        ];
    }

    /**
     * Valida y resuelve el video asociado a una escena para reproducción controlada en el visor.
     */
    public function getSceneVideoForVisitor(int $sceneId, ?int $viewerUserId = null): ?array {
        if ($this->db === null || $sceneId <= 0 || !$this->tablesReady()) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT rv.archivo_interno, rv.mime_type, r.estado AS recorrido_estado,
                    p.id_propietario, p.fecha_retiro, e.nombre AS propiedad_estado
             FROM recorrido_escena re
             INNER JOIN recorrido r ON r.id_recorrido = re.id_recorrido
             INNER JOIN recorrido_video rv ON rv.id_video = re.video_id
             INNER JOIN propiedad p ON p.id_propiedad = r.id_propiedad
             INNER JOIN estado_propiedad e ON e.id_estado = p.id_estado
             WHERE re.id_escena = ? AND rv.estado = 'LISTO'
             LIMIT 1"
        );
        $stmt->execute([$sceneId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $isOwner = $viewerUserId !== null && $viewerUserId > 0 && (int) $row['id_propietario'] === $viewerUserId;
        $isPublic = $row['fecha_retiro'] === null
            && $row['propiedad_estado'] === 'DISPONIBLE'
            && $row['recorrido_estado'] === 'PUBLICADO';

        if (!$isPublic && !$isOwner) {
            return null;
        }

        $path = $this->safeVideoPath((string) $row['archivo_interno']);
        if ($path === null || !is_file($path)) {
            return null;
        }

        return [
            'path'          => $path,
            'mime_type'     => (string) $row['mime_type'],
            'cache_control' => $isPublic ? 'public, max-age=300' : 'private, no-store',
        ];
    }

    /**
     * Consulta ligera usada por Propiedad::getAll() para saber qué propiedades tienen recorrido publicado.
     */
    public function fetchPublishedByPropertyMap(): array {
        if ($this->db === null || !$this->tablesReady()) {
            return [];
        }

        try {
            $stmt = $this->db->query(
                "SELECT r.id_recorrido, r.id_propiedad, r.titulo, COUNT(re.id_escena) AS total_escenas
                 FROM recorrido r
                 INNER JOIN recorrido_escena re ON re.id_recorrido = r.id_recorrido
                 WHERE r.estado = 'PUBLICADO'
                 GROUP BY r.id_recorrido, r.id_propiedad, r.titulo
                 HAVING total_escenas > 0"
            );
            $map = [];
            foreach ($stmt->fetchAll() as $row) {
                $map[(int) $row['id_propiedad']] = [
                    'id_recorrido'  => (int) $row['id_recorrido'],
                    'titulo'        => (string) $row['titulo'],
                    'total_escenas' => (int) $row['total_escenas'],
                ];
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function findAuthorizedVideo(int $tourId, int $propertyId, int $videoId): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM recorrido_video
             WHERE id_video = ? AND id_recorrido = ? AND id_propiedad = ? AND estado = 'LISTO'
             LIMIT 1"
        );
        $stmt->execute([$videoId, $tourId, $propertyId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function validateSceneTiming(array $data, ?float $maxDuration, bool $requireMoment): array {
        $rawMoment = $data['momento_segundos'] ?? null;
        $momento = null;
        if ($rawMoment !== null && $rawMoment !== '') {
            if (!is_numeric($rawMoment) || !is_finite((float) $rawMoment) || (float) $rawMoment < 0) {
                return ['ok' => false, 'message' => 'El momento seleccionado del video debe ser un número mayor o igual a 0.'];
            }
            $momento = round((float) $rawMoment, 2);
        } elseif ($requireMoment) {
            return ['ok' => false, 'message' => 'Selecciona un momento válido del video para extraer la escena.'];
        }

        $rawStart = $data['inicio_segundos'] ?? null;
        $rawEnd = $data['fin_segundos'] ?? null;

        $inicio = null;
        $fin = null;

        if ($rawStart !== null && $rawStart !== '') {
            if (!is_numeric($rawStart) || !is_finite((float) $rawStart) || (float) $rawStart < 0) {
                return ['ok' => false, 'message' => 'El inicio del fragmento debe ser mayor o igual a 0 segundos.'];
            }
            $inicio = round((float) $rawStart, 2);
        }

        if ($rawEnd !== null && $rawEnd !== '') {
            if (!is_numeric($rawEnd) || !is_finite((float) $rawEnd) || (float) $rawEnd <= 0) {
                return ['ok' => false, 'message' => 'El fin del fragmento debe ser mayor a 0 segundos.'];
            }
            $fin = round((float) $rawEnd, 2);
        }

        if (($inicio !== null && $fin === null) || ($inicio === null && $fin !== null)) {
            return ['ok' => false, 'message' => 'Si defines un fragmento de video, especifica tanto el segundo de inicio como el de fin (o deja ambos vacíos).'];
        }

        if ($inicio !== null && $fin !== null && $fin <= $inicio) {
            return ['ok' => false, 'message' => 'El segundo final del fragmento debe ser mayor que el segundo inicial.'];
        }

        $hardLimit = $maxDuration !== null ? ($maxDuration + 0.5) : self::MAX_DURATION_SECONDS;
        if ($momento !== null && $momento > $hardLimit) {
            return ['ok' => false, 'message' => 'El momento seleccionado excede la duración del video.'];
        }
        if ($fin !== null && $fin > $hardLimit) {
            return ['ok' => false, 'message' => 'El fin del fragmento excede la duración del video.'];
        }

        return ['ok' => true, 'momento' => $momento, 'inicio' => $inicio, 'fin' => $fin];
    }

    /**
     * Ejecuta la extracción con FFmpeg o valida la captura enviada por el navegador,
     * garantizando limpieza de temporales ante cualquier falla.
     */
    private function resolveAndPersistSceneImage(
        int $tourId,
        array $video,
        float $momento,
        string $mode,
        ?array $capturedFrameFile
    ): array {
        if (!in_array($mode, ['auto', 'ffmpeg', 'navegador'], true)) {
            $mode = 'auto';
        }

        $this->ensureDirectories();
        $tempFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'tmp_' . bin2hex(random_bytes(8)) . '.bin';
        $hasUpload = is_array($capturedFrameFile) && ($capturedFrameFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $videoPath = $this->safeVideoPath((string) $video['archivo_interno']);
        $ffmpegAvailable = VideoProcessor::resolveBinary('FFMPEG_PATH', 'ffmpeg') !== null;

        try {
            $origen = 'navegador';
            $ext = 'jpg';

            if ($mode === 'ffmpeg' || ($mode === 'auto' && $ffmpegAvailable)) {
                if ($videoPath === null) {
                    return ['ok' => false, 'code' => 404, 'message' => 'El archivo de video original no está disponible.'];
                }
                $tempJpg = $tempFile . '.jpg';
                $ffRes = VideoProcessor::extractFrame($videoPath, $momento, $tempJpg);
                if (!$ffRes['ok']) {
                    @unlink($tempJpg);
                    if ($mode === 'auto' && $hasUpload) {
                        // Fallback explícito si el usuario también envió captura del navegador.
                        $origen = 'navegador';
                    } else {
                        $httpCode = ($ffRes['code'] ?? '') === 'ffmpeg_unavailable' ? 503 : 422;
                        return ['ok' => false, 'code' => $httpCode, 'message' => $ffRes['message']];
                    }
                } else {
                    $tempFile = $tempJpg;
                    $origen = 'ffmpeg';
                }
            }

            if ($origen === 'navegador') {
                if (!$hasUpload) {
                    return [
                        'ok'      => false,
                        'code'    => $ffmpegAvailable ? 422 : 503,
                        'message' => $ffmpegAvailable
                            ? 'No se recibió la imagen del fotograma.'
                            : 'FFmpeg no está instalado en el servidor y no se recibió captura del reproductor del navegador.',
                    ];
                }

                $err = (int) ($capturedFrameFile['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($err !== UPLOAD_ERR_OK) {
                    return ['ok' => false, 'code' => 422, 'message' => 'Error al subir la imagen extraída de la escena.'];
                }

                $tmpUpload = (string) ($capturedFrameFile['tmp_name'] ?? '');
                $size = (int) ($capturedFrameFile['size'] ?? 0);
                if ($tmpUpload === '' || !is_uploaded_file($tmpUpload) || $size <= 0 || $size > self::MAX_IMAGE_BYTES) {
                    return ['ok' => false, 'code' => 422, 'message' => 'La imagen capturada es inválida o supera 5 MB.'];
                }

                if (!move_uploaded_file($tmpUpload, $tempFile)) {
                    return ['ok' => false, 'code' => 500, 'message' => 'No se pudo procesar temporalmente la imagen capturada.'];
                }

                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                $mime = $finfo ? (string) finfo_file($finfo, $tempFile) : '';
                if ($finfo) {
                    finfo_close($finfo);
                }

                if (!isset(self::ALLOWED_IMAGE_MIMES[$mime])) {
                    return ['ok' => false, 'code' => 422, 'message' => 'La imagen capturada debe ser JPG, PNG o WebP válido.'];
                }
                $ext = self::ALLOWED_IMAGE_MIMES[$mime];
            }

            $dimensions = @getimagesize($tempFile);
            if ($dimensions === false || ($dimensions[0] ?? 0) < 160 || ($dimensions[1] ?? 0) < 90 || ($dimensions[0] ?? 0) > 4096 || ($dimensions[1] ?? 0) > 4096) {
                return ['ok' => false, 'code' => 422, 'message' => 'Las dimensiones de la imagen de escena no son válidas (mínimo 160×90 px).'];
            }

            $finalName = 'esc_' . $tourId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $finalPath = $this->scenesPublicDir . DIRECTORY_SEPARATOR . $finalName;

            if (!@rename($tempFile, $finalPath)) {
                return ['ok' => false, 'code' => 500, 'message' => 'No se pudo guardar la imagen final de la escena.'];
            }

            return [
                'ok'           => true,
                'origen'       => $origen,
                'full_path'    => $finalPath,
                'relative_url' => 'uploads/recorridos/escenas/' . $finalName,
            ];
        } finally {
            // Limpieza garantizada de archivos temporales ante error o fallo intermedio.
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
            if (is_file($tempFile . '.jpg')) {
                @unlink($tempFile . '.jpg');
            }
        }
    }

    private function validatePositionPayload($rawPos, array $fallbackData): array {
        $pos = is_array($rawPos) ? $rawPos : [];
        if (empty($pos) && isset($fallbackData['posicion_json']) && is_string($fallbackData['posicion_json'])) {
            $decoded = json_decode($fallbackData['posicion_json'], true);
            if (is_array($decoded)) {
                $pos = $decoded;
            }
        }
        if (empty($pos) && isset($fallbackData['x'], $fallbackData['y'])) {
            $pos = ['sistema' => 'plano_xy', 'x' => $fallbackData['x'], 'y' => $fallbackData['y']];
        }

        $sistema = (string) ($pos['sistema'] ?? 'plano_xy');
        if ($sistema === 'plano_xy') {
            $x = $pos['x'] ?? null;
            $y = $pos['y'] ?? null;
            if (!is_numeric($x) || !is_numeric($y) || (float) $x < 0 || (float) $x > 100 || (float) $y < 0 || (float) $y > 100) {
                return ['ok' => false, 'message' => 'Las coordenadas x, y en imagen plana deben estar entre 0 y 100 (%).'];
            }
            return [
                'ok'       => true,
                'sistema'  => 'plano_xy',
                'posicion' => [
                    'sistema' => 'plano_xy',
                    'x'       => round((float) $x, 2),
                    'y'       => round((float) $y, 2),
                ],
            ];
        }

        if ($sistema === 'panorama_yaw_pitch') {
            $yaw = $pos['yaw'] ?? null;
            $pitch = $pos['pitch'] ?? null;
            if (!is_numeric($yaw) || !is_numeric($pitch) || (float) $yaw < -180 || (float) $yaw > 180 || (float) $pitch < -90 || (float) $pitch > 90) {
                return ['ok' => false, 'message' => 'Las coordenadas yaw (-180 a 180) y pitch (-90 a 90) no son válidas.'];
            }
            return [
                'ok'       => true,
                'sistema'  => 'panorama_yaw_pitch',
                'posicion' => [
                    'sistema' => 'panorama_yaw_pitch',
                    'yaw'     => round((float) $yaw, 2),
                    'pitch'   => round((float) $pitch, 2),
                ],
            ];
        }

        return ['ok' => false, 'message' => 'Sistema de coordenadas no reconocido.'];
    }

    private function fetchScenes(int $tourId, bool $publicMode): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM recorrido_escena
             WHERE id_recorrido = ?
             ORDER BY orden ASC, id_escena ASC"
        );
        $stmt->execute([$tourId]);
        return array_map(
            fn(array $row): array => $this->formatScene($row, $publicMode),
            $stmt->fetchAll()
        );
    }

    private function fetchSingleScene(int $tourId, int $sceneId, bool $publicMode): ?array {
        $stmt = $this->db->prepare('SELECT * FROM recorrido_escena WHERE id_recorrido = ? AND id_escena = ? LIMIT 1');
        $stmt->execute([$tourId, $sceneId]);
        $row = $stmt->fetch();
        return $row ? $this->formatScene($row, $publicMode) : null;
    }

    private function fetchConnections(int $tourId): array {
        $stmt = $this->db->prepare('SELECT * FROM recorrido_conexion WHERE id_recorrido = ? ORDER BY id_conexion ASC');
        $stmt->execute([$tourId]);
        return array_map(
            fn(array $row): array => $this->formatConnection($row),
            $stmt->fetchAll()
        );
    }

    private function formatTour(array $row): array {
        return [
            'id'                => (int) $row['id_recorrido'],
            'id_propiedad'      => (int) $row['id_propiedad'],
            'titulo'            => (string) $row['titulo'],
            'descripcion'       => (string) ($row['descripcion'] ?? ''),
            'estado'            => (string) $row['estado'],
            'escena_inicial_id' => $row['escena_inicial_id'] !== null ? (int) $row['escena_inicial_id'] : null,
        ];
    }

    private function formatVideo(array $row, int $propertyId): array {
        $id = (int) $row['id_video'];
        return [
            'id'                => $id,
            'id_recorrido'      => (int) $row['id_recorrido'],
            'id_propiedad'      => (int) $row['id_propiedad'],
            'nombre_original'   => (string) $row['nombre_original'],
            'mime_type'         => (string) $row['mime_type'],
            'tamano_bytes'      => (int) $row['tamano_bytes'],
            'duracion_segundos' => $row['duracion_segundos'] !== null ? (float) $row['duracion_segundos'] : null,
            'ancho'             => $row['ancho'] !== null ? (int) $row['ancho'] : null,
            'alto'              => $row['alto'] !== null ? (int) $row['alto'] : null,
            'estado'            => (string) $row['estado'],
            'fecha_subida'      => (string) $row['fecha_subida'],
            'stream_url'        => BASE_URL . 'api/mis-propiedades/' . $propertyId . '/recorrido-video/videos/' . $id . '/stream',
        ];
    }

    private function formatScene(array $row, bool $publicMode): array {
        $id = (int) $row['id_escena'];
        $videoId = $row['video_id'] !== null ? (int) $row['video_id'] : null;

        return [
            'id'                  => $id,
            'id_recorrido'        => (int) $row['id_recorrido'],
            'nombre'              => (string) $row['nombre'],
            'tipo_visual'         => (string) $row['tipo_visual'],
            'imagen_url'          => Propiedad::resolveAssetUrl((string) $row['imagen_url']),
            'imagen_ruta'         => (string) $row['imagen_url'],
            'video_id'            => $videoId,
            'momento_segundos'    => $row['momento_segundos'] !== null ? (float) $row['momento_segundos'] : null,
            'inicio_segundos'     => $row['inicio_segundos'] !== null ? (float) $row['inicio_segundos'] : null,
            'fin_segundos'        => $row['fin_segundos'] !== null ? (float) $row['fin_segundos'] : null,
            'orden'               => (int) $row['orden'],
            'origen_extraccion'   => (string) $row['origen_extraccion'],
            'video_fragmento_url' => $videoId !== null
                ? (BASE_URL . 'api/recorridos/escenas/' . $id . '/video')
                : null,
        ];
    }

    private function formatConnection(array $row): array {
        $pos = json_decode((string) $row['posicion_json'], true);
        if (!is_array($pos)) {
            $pos = ['sistema' => (string) $row['posicion_sistema'], 'x' => 50, 'y' => 50];
        }
        return [
            'id'                => (int) $row['id_conexion'],
            'escena_origen_id'  => (int) $row['escena_origen_id'],
            'escena_destino_id' => (int) $row['escena_destino_id'],
            'etiqueta'          => (string) $row['etiqueta'],
            'posicion'          => $pos,
        ];
    }

    private function safeVideoPath(string $filename): ?string {
        $base = basename($filename);
        if ($base === '' || $base !== $filename) {
            return null;
        }
        $candidate = $this->videosDir . DIRECTORY_SEPARATOR . $base;
        $realBase = realpath($this->videosDir);
        if ($realBase === false) {
            return null;
        }
        $realFile = realpath($candidate);
        if ($realFile !== false && !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $candidate;
    }

    private function deleteSceneImageFile(string $relativeUrl): void {
        $clean = ltrim(str_replace('\\', '/', $relativeUrl), '/');
        if (!str_starts_with($clean, 'uploads/recorridos/escenas/')) {
            return;
        }
        $filename = basename($clean);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return;
        }
        $target = $this->scenesPublicDir . DIRECTORY_SEPARATOR . $filename;
        if (is_file($target)) {
            @unlink($target);
        }
    }

    private function sanitizeDisplayFilename(string $raw): string {
        $base = basename(str_replace('\\', '/', $raw));
        $clean = trim((string) preg_replace('/[^\p{L}\p{N}._\- ]+/u', '_', $base));
        return mb_substr($clean !== '' ? $clean : 'video.mp4', 0, 240);
    }
}

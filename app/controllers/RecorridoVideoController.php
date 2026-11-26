<?php
/**
 * Controlador de carga de video, extracción de escenas y streaming autorizado
 * sobre el editor y visor unificado de recorridos.
 * INFONATEC Inmobiliaria
 */

class RecorridoVideoController extends Controller {
    private function requireUserId(): int {
        $id = (int) (Usuario::current()['id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Inicia sesión para administrar los videos de tu recorrido.', 401);
        }
        return $id;
    }

    private function optionalUserId(): ?int {
        $id = (int) (Usuario::current()['id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function parsePositiveId(string $raw): int {
        if (!ctype_digit($raw) || (int) $raw <= 0) {
            throw new RuntimeException('Identificador inválido.', 400);
        }
        return (int) $raw;
    }

    private function rejectOversizedPostIfEmpty(): void {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && !str_contains($contentType, 'application/json')
            && empty($_POST)
            && empty($_FILES)
            && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
        ) {
            $maxMb = round(VideoProcessor::getEffectiveMaxVideoBytes() / 1048576, 1);
            $this->json([
                'success' => false,
                'message' => "La carga supera el tamaño máximo permitido por el servidor ({$maxMb} MB).",
            ], 413);
        }
    }

    private function failure(Throwable $e): void {
        $status = (int) $e->getCode();
        if (!in_array($status, [400, 401, 403, 404, 409, 413, 422, 503], true)) {
            error_log('RecorridoVideo: ' . $e->getMessage());
            $status = 500;
        }
        $message = $status === 500
            ? 'No fue posible procesar la solicitud del video. Inténtalo de nuevo.'
            : $e->getMessage();
        $this->json(['success' => false, 'message' => $message], $status);
    }

    public function status(): void {
        try {
            $this->requireUserId();
            $this->json([
                'success'    => true,
                'procesador' => VideoProcessor::getStatus(),
            ]);
        } catch (Throwable $e) {
            $this->failure($e);
        }
    }

    public function listVideos(string $id): void {
        try {
            $userId = $this->requireUserId();
            $propertyId = $this->parsePositiveId($id);
            $videos = (new RecorridoVideo())->listVideos($propertyId, $userId);
            $this->json([
                'success'    => true,
                'procesador' => VideoProcessor::getStatus(),
                'videos'     => $videos,
            ]);
        } catch (Throwable $e) {
            $this->failure($e);
        }
    }

    public function uploadVideo(string $id): void {
        try {
            $userId = $this->requireUserId();
            Security::requireCsrf();
            $this->rejectOversizedPostIfEmpty();

            $propertyId = $this->parsePositiveId($id);
            $duration = isset($_POST['duracion_segundos']) && is_numeric($_POST['duracion_segundos'])
                ? (float) $_POST['duracion_segundos']
                : null;
            $width = isset($_POST['ancho']) && is_numeric($_POST['ancho']) ? (int) $_POST['ancho'] : null;
            $height = isset($_POST['alto']) && is_numeric($_POST['alto']) ? (int) $_POST['alto'] : null;

            $video = (new RecorridoVideo())->uploadVideo(
                $propertyId,
                $userId,
                $_FILES['video'] ?? [],
                $duration,
                $width,
                $height
            );

            $this->json([
                'success' => true,
                'message' => 'Video cargado correctamente en almacenamiento protegido.',
                'video'   => $video,
            ], 201);
        } catch (Throwable $e) {
            $this->failure($e);
        }
    }

    public function streamOwnerVideo(string $id, string $videoId): void {
        try {
            $userId = $this->requireUserId();
            $propertyId = $this->parsePositiveId($id);
            $vidId = $this->parsePositiveId($videoId);

            $video = (new RecorridoVideo())->getOwnerVideoFile($propertyId, $userId, $vidId);
            VideoProcessor::streamVideoRange($video['path'], $video['mime_type'], 'private, no-store');
        } catch (Throwable $e) {
            $this->failure($e);
        }
    }

    public function deleteVideo(string $id, string $videoId): void {
        try {
            $userId = $this->requireUserId();
            $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
            $input = str_contains($contentType, 'application/json')
                ? (json_decode((string) file_get_contents('php://input'), true) ?: [])
                : $_POST;
            Security::requireCsrf(is_array($input) ? $input : null);

            $propertyId = $this->parsePositiveId($id);
            $vidId = $this->parsePositiveId($videoId);

            (new RecorridoVideo())->deleteVideo($propertyId, $userId, $vidId);
            $this->json(['success' => true, 'message' => 'Video eliminado correctamente.']);
        } catch (Throwable $e) {
            $this->failure($e);
        }
    }

    public function extractScene(string $id): void {
        try {
            $userId = $this->requireUserId();
            Security::requireCsrf();
            $this->rejectOversizedPostIfEmpty();

            $propertyId = $this->parsePositiveId($id);
            $result = (new RecorridoVideo())->extractScene($propertyId, $userId, $_POST, $_FILES);

            $this->json([
                'success'           => true,
                'message'           => 'Escena extraída y lista en el editor compartido.',
                'origen_extraccion' => $result['origen_extraccion'],
                'scene'             => $result['scene'],
            ], 201);
        } catch (Throwable $e) {
            $this->failure($e);
        }
    }

    public function streamSceneVideo(string $id, string $escenaId): void {
        try {
            $propertyId = $this->parsePositiveId($id);
            $sceneId = $this->parsePositiveId($escenaId);
            $preview = ($_GET['preview'] ?? '') === '1';

            $video = (new RecorridoVideo())->getAuthorizedSceneVideoFile(
                $propertyId,
                $sceneId,
                $this->optionalUserId(),
                $preview
            );

            VideoProcessor::streamVideoRange($video['path'], $video['mime_type'], 'private, no-store');
        } catch (Throwable $e) {
            $this->failure($e);
        }
    }

    public function redirectToEditor(string $id): void {
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->redirect('mis-propiedades');
        }
        $this->redirect('mis-propiedades/' . (int) $id . '/recorrido#video');
    }

    public function redirectToViewer(string $id): void {
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->redirect('explorar');
        }
        $preview = ($_GET['preview'] ?? '') === '1' ? '?preview=1' : '';
        $this->redirect('recorridos/' . (int) $id . $preview);
    }
}

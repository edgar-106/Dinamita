<?php
/**
 * Controlador de recorridos por escenas desde video (Propietario y Visor).
 * INFONATEC Inmobiliaria
 */

class RecorridoVideoController extends Controller {
    private function requireOwner(bool $isApi = true): array {
        $user = Usuario::current();
        if (empty($user['id'])) {
            if ($isApi) {
                $this->json(['success' => false, 'message' => 'Inicia sesión para administrar tus recorridos.'], 401);
            }
            $this->renderView('cuenta/propiedades', [
                'pageTitle'  => 'Recorrido por video | INFONATEC',
                'activePage' => 'mis-propiedades',
                'owner'      => null,
                'listings'   => [],
            ]);
            exit;
        }
        return $user;
    }

    private function parseInput(): array {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        return is_array($_POST) ? $_POST : [];
    }

    private function parsePositiveId(string $raw, bool $asJson = true): int {
        if (!ctype_digit($raw) || (int) $raw <= 0) {
            if ($asJson) {
                $this->json(['success' => false, 'message' => 'Identificador inválido.'], 400);
            }
            http_response_code(400);
            echo '<h1>400</h1><p>Identificador inválido.</p>';
            exit;
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

    public function studio(string $id): void {
        $user = $this->requireOwner(false);
        $propertyId = $this->parsePositiveId($id, false);
        $model = new RecorridoVideo();

        try {
            $bundle = $model->getOwnerTourBundle((int) $user['id'], $propertyId);
        } catch (Throwable $e) {
            http_response_code(500);
            $this->renderView('cuenta/propiedades', [
                'pageTitle'  => 'Recorrido por video | INFONATEC',
                'activePage' => 'mis-propiedades',
                'owner'      => $user,
                'listings'   => [],
                'error'      => $e->getMessage(),
            ]);
            return;
        }

        if ($bundle === null) {
            http_response_code(404);
            $this->renderView('cuenta/propiedades', [
                'pageTitle'  => 'Publicación no encontrada | INFONATEC',
                'activePage' => 'mis-propiedades',
                'owner'      => $user,
                'listings'   => [],
                'error'      => 'La publicación no existe o no pertenece a tu cuenta.',
            ]);
            return;
        }

        $this->renderView('cuenta/recorrido_video', [
            'pageTitle'  => 'Recorrido por video | ' . $bundle['propiedad']['titulo'],
            'activePage' => 'mis-propiedades',
            'owner'      => $user,
            'bundle'     => $bundle,
        ]);
    }

    public function viewer(string $id): void {
        $propertyId = $this->parsePositiveId($id, false);
        $viewer = Usuario::current();
        $viewerId = !empty($viewer['id']) ? (int) $viewer['id'] : null;

        $bundle = (new RecorridoVideo())->getPublicTourBundle($propertyId, $viewerId);
        if ($bundle === null) {
            http_response_code(404);
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Recorrido no disponible</title></head>'
                . '<body style="font-family:sans-serif;background:#111;color:#fff;padding:40px;text-align:center;">'
                . '<h1>Recorrido no disponible</h1><p>Esta propiedad aún no tiene un recorrido por escenas publicado.</p>'
                . '<p><a href="' . htmlspecialchars(BASE_URL . 'explorar', ENT_QUOTES, 'UTF-8') . '" style="color:#d4af37;">Volver al catálogo</a></p>'
                . '</body></html>';
            return;
        }

        $this->renderView('tour/escenas', [
            'bundle'   => $bundle,
            'embedded' => ($_GET['embed'] ?? '') === '1',
        ], false);
    }

    public function status(): void {
        $this->requireOwner(true);
        $this->json([
            'success'    => true,
            'procesador' => VideoProcessor::getStatus(),
        ]);
    }

    public function ownerTourData(string $id): void {
        $user = $this->requireOwner(true);
        $propertyId = $this->parsePositiveId($id, true);

        try {
            $bundle = (new RecorridoVideo())->getOwnerTourBundle((int) $user['id'], $propertyId);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        if ($bundle === null) {
            $this->json(['success' => false, 'message' => 'La publicación no existe o no pertenece a tu cuenta.'], 404);
        }

        $this->json($bundle);
    }

    public function uploadVideo(string $id): void {
        $user = $this->requireOwner(true);
        Security::requireCsrf();
        $this->rejectOversizedPostIfEmpty();

        $propertyId = $this->parsePositiveId($id, true);
        $duration = isset($_POST['duracion_segundos']) && is_numeric($_POST['duracion_segundos'])
            ? (float) $_POST['duracion_segundos'] : null;
        $width = isset($_POST['ancho']) && is_numeric($_POST['ancho']) ? (int) $_POST['ancho'] : null;
        $height = isset($_POST['alto']) && is_numeric($_POST['alto']) ? (int) $_POST['alto'] : null;

        $result = (new RecorridoVideo())->saveUploadedVideo(
            (int) $user['id'],
            $propertyId,
            $_FILES['video'] ?? null,
            $duration,
            $width,
            $height
        );

        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function streamOwnerVideo(string $id, string $videoId): void {
        $user = $this->requireOwner(true);
        $propertyId = $this->parsePositiveId($id, true);
        $vidId = $this->parsePositiveId($videoId, true);

        $video = (new RecorridoVideo())->getOwnerVideoForStreaming((int) $user['id'], $propertyId, $vidId);
        if ($video === null) {
            $this->json(['success' => false, 'message' => 'Video no encontrado o sin permiso de acceso.'], 404);
        }

        VideoProcessor::streamVideoRange($video['path'], $video['mime_type'], 'private, no-store');
    }

    public function deleteVideo(string $id, string $videoId): void {
        $user = $this->requireOwner(true);
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $propertyId = $this->parsePositiveId($id, true);
        $vidId = $this->parsePositiveId($videoId, true);

        $result = (new RecorridoVideo())->deleteVideo((int) $user['id'], $propertyId, $vidId);
        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function createScene(string $id): void {
        $user = $this->requireOwner(true);
        $input = $this->parseInput();
        Security::requireCsrf($input);
        $this->rejectOversizedPostIfEmpty();

        $propertyId = $this->parsePositiveId($id, true);
        $result = (new RecorridoVideo())->createScene(
            (int) $user['id'],
            $propertyId,
            $input,
            $_FILES['frame_image'] ?? null
        );

        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function updateScene(string $id, string $escenaId): void {
        $user = $this->requireOwner(true);
        $input = $this->parseInput();
        Security::requireCsrf($input);
        $this->rejectOversizedPostIfEmpty();

        $propertyId = $this->parsePositiveId($id, true);
        $sceneId = $this->parsePositiveId($escenaId, true);

        $result = (new RecorridoVideo())->updateScene(
            (int) $user['id'],
            $propertyId,
            $sceneId,
            $input,
            $_FILES['frame_image'] ?? null
        );

        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function deleteScene(string $id, string $escenaId): void {
        $user = $this->requireOwner(true);
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $propertyId = $this->parsePositiveId($id, true);
        $sceneId = $this->parsePositiveId($escenaId, true);

        $result = (new RecorridoVideo())->deleteScene((int) $user['id'], $propertyId, $sceneId);
        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function createConnection(string $id): void {
        $user = $this->requireOwner(true);
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $propertyId = $this->parsePositiveId($id, true);
        $result = (new RecorridoVideo())->createConnection((int) $user['id'], $propertyId, $input);
        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function deleteConnection(string $id, string $conexionId): void {
        $user = $this->requireOwner(true);
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $propertyId = $this->parsePositiveId($id, true);
        $connId = $this->parsePositiveId($conexionId, true);

        $result = (new RecorridoVideo())->deleteConnection((int) $user['id'], $propertyId, $connId);
        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function updateTourState(string $id): void {
        $user = $this->requireOwner(true);
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $propertyId = $this->parsePositiveId($id, true);
        $estado = (string) ($input['estado'] ?? '');
        $initialSceneId = isset($input['escena_inicial_id']) && is_numeric($input['escena_inicial_id'])
            ? (int) $input['escena_inicial_id'] : null;

        $result = (new RecorridoVideo())->updateTourState((int) $user['id'], $propertyId, $estado, $initialSceneId);
        $this->json($result, (int) ($result['code'] ?? ($result['success'] ? 200 : 400)));
    }

    public function publicTourData(string $id): void {
        $propertyId = $this->parsePositiveId($id, true);
        $viewer = Usuario::current();
        $viewerId = !empty($viewer['id']) ? (int) $viewer['id'] : null;

        $bundle = (new RecorridoVideo())->getPublicTourBundle($propertyId, $viewerId);
        if ($bundle === null) {
            $this->json(['success' => false, 'message' => 'Recorrido no encontrado o aún en borrador.'], 404);
        }

        $this->json($bundle);
    }

    public function streamPublicSceneVideo(string $escenaId): void {
        $sceneId = $this->parsePositiveId($escenaId, true);
        $viewer = Usuario::current();
        $viewerId = !empty($viewer['id']) ? (int) $viewer['id'] : null;

        $video = (new RecorridoVideo())->getSceneVideoForVisitor($sceneId, $viewerId);
        if ($video === null) {
            $this->json(['success' => false, 'message' => 'Fragmento de video no disponible.'], 404);
        }

        VideoProcessor::streamVideoRange($video['path'], $video['mime_type'], $video['cache_control']);
    }
}

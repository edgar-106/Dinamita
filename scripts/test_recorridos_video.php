<?php
/**
 * Suite de pruebas integrales del módulo de recorridos por video (INFONATEC).
 * Ejecutar: C:\xampp\php\php.exe scripts/test_recorridos_video.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo CLI\n");
}

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/Database.php';

$ROOT = dirname(__DIR__);
$BASE_URL = 'http://localhost/Bienes%20raices/public/';
$PROJECT_ROOT_URL = 'http://localhost/Bienes%20raices/';

class HttpClient {
    public string $cookieFile;
    public string $csrf = '';

    public function __construct() {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'inf_cookie_');
    }

    public function __destruct() {
        @unlink($this->cookieFile);
    }

    public function request(string $method, string $url, $body = null, array $headers = [], bool $includeCsrf = true): array {
        $ch = curl_init($url);
        $reqHeaders = $headers;
        if ($includeCsrf && $this->csrf !== '' && strtoupper($method) === 'POST') {
            $reqHeaders[] = 'X-CSRF-Token: ' . $this->csrf;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $reqHeaders,
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Error cURL en ' . $url . ': ' . $err);
        }

        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($raw, 0, $headerSize);
        $content = substr($raw, $headerSize);
        $json = json_decode($content, true);

        return [
            'status'  => $code,
            'headers' => $rawHeaders,
            'body'    => $content,
            'json'    => is_array($json) ? $json : null,
        ];
    }

    public function refreshCsrf(string $baseUrl): void {
        $res = $this->request('GET', $baseUrl . 'mis-propiedades', null, [], false);
        if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $res['body'], $m)) {
            $this->csrf = $m[1];
        }
    }
}

$passed = 0;
$failed = 0;
function check(string $name, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[OK]   {$name}\n";
    } else {
        $failed++;
        echo "[FAIL] {$name}" . ($detail !== '' ? " -> {$detail}" : '') . "\n";
    }
}

$db = Database::getConnection();
if ($db === null) {
    exit("ERROR: No hay conexión a MySQL.\n");
}

$tempFilesToClean = [];
$createdUserIds = [];
$createdPropId = 0;
$createdVideoFile = null;
$createdSceneFiles = [];

try {
    $guest = new HttpClient();
    $ownerA = new HttpClient();
    $attackerB = new HttpClient();

    // 1. Regresión básica de rutas existentes
    foreach (['', 'explorar', 'api/propiedades', 'tour?prop=2', 'mis-propiedades'] as $route) {
        $r = $guest->request('GET', $BASE_URL . $route);
        check("Regresión ruta pública /{$route}", $r['status'] === 200, "HTTP {$r['status']}");
    }
    $prop2 = $guest->request('GET', $BASE_URL . 'api/propiedades/2');
    check('Propiedad 2 conserva tour 360 existente intacto', $prop2['status'] === 200 && !empty($prop2['json']['virtualTour']) && empty($prop2['json']['virtualTour']['isSceneTour']));

    // 2. Registrar dos cuentas temporales
    $pwd = bin2hex(random_bytes(8)) . 'A1!';
    $emailA = 'qa_vid_a_' . bin2hex(random_bytes(4)) . '@example.invalid';
    $emailB = 'qa_vid_b_' . bin2hex(random_bytes(4)) . '@example.invalid';

    foreach ([['A', $ownerA, $emailA], ['B', $attackerB, $emailB]] as [$lbl, $client, $email]) {
        $client->refreshCsrf($BASE_URL);
        $r = $client->request('POST', $BASE_URL . 'auth/register', http_build_query([
            'name' => 'Propietario',
            'last' => 'Prueba ' . $lbl,
            'email' => $email,
            'phone' => '9511112233',
            'password' => $pwd,
        ]), ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']);
        check("Registrar cuenta temporal {$lbl}", $r['status'] === 200 && !empty($r['json']['success']));
        $createdUserIds[] = (int) ($r['json']['user']['id'] ?? 0);
        if (!empty($r['json']['csrf_token'])) {
            $client->csrf = $r['json']['csrf_token'];
        }
    }

    // 3. Publicar propiedad de prueba como Propietario A
    $samplePhoto = $ROOT . '/public/tour/panoramas/sala.jpg';
    $pubRes = $ownerA->request('POST', $BASE_URL . 'vender/publicar', [
        'title' => 'Casa QA Recorrido Video',
        'type' => 'CASA',
        'operation' => 'VENTA',
        'price' => '3200000',
        'location' => 'Centro, Oaxaca',
        'description' => 'Propiedad de prueba temporal para validar recorridos interactivos por video.',
        'area' => '180',
        'bedrooms' => '3',
        'bathrooms' => '2',
        'owner_phone' => '9511112233',
        'photos[0]' => new CURLFile($samplePhoto, 'image/jpeg', 'sala.jpg'),
    ], ['Accept: application/json']);
    check('Publicar propiedad de prueba (Cuenta A)', $pubRes['status'] === 200 && !empty($pubRes['json']['success']));
    $createdPropId = (int) ($pubRes['json']['id'] ?? 0);

    // 4. Acceso sin autenticación rechazado
    $unauthApi = $guest->request('GET', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video");
    check('Invitado recibe 401 en API privada', $unauthApi['status'] === 401);
    $unauthStudio = $guest->request('GET', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido-video");
    check('Invitado no accede al estudio (pide login)', str_contains($unauthStudio['body'], 'Ingresa a tu cuenta'));

    // 5. Protección CSRF en endpoints POST
    $noCsrfVid = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [], ['Accept: application/json'], false);
    check('POST video sin CSRF rechazado (403)', $noCsrfVid['status'] === 403 && ($noCsrfVid['json']['error'] ?? '') === 'csrf_invalid');
    $noCsrfScene = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", json_encode(['nombre' => 'Sala']), ['Content-Type: application/json'], false);
    check('POST escena sin CSRF rechazado (403)', $noCsrfScene['status'] === 403);

    // 6. Rechazo de archivos inválidos y demasiado grandes
    $fakePhp = tempnam(sys_get_temp_dir(), 'fake_');
    file_put_contents($fakePhp, "<?php echo 'exploit'; ?>");
    $tempFilesToClean[] = $fakePhp;
    $badPhpRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'video' => new CURLFile($fakePhp, 'video/mp4', 'malicioso.mp4'),
    ], ['Accept: application/json']);
    check('Rechaza archivo PHP disfrazado de MP4 (422)', $badPhpRes['status'] === 422);

    $txtFile = tempnam(sys_get_temp_dir(), 'txt_');
    file_put_contents($txtFile, 'Esto no es un video real');
    $tempFilesToClean[] = $txtFile;
    $badTxtRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'video' => new CURLFile($txtFile, 'text/plain', 'nota.txt'),
    ], ['Accept: application/json']);
    check('Rechaza archivo de texto inválido (422)', $badTxtRes['status'] === 422);

    $bigFile = tempnam(sys_get_temp_dir(), 'big_');
    $fpBig = fopen($bigFile, 'wb');
    fseek($fpBig, (36 * 1048576) - 1);
    fwrite($fpBig, "\0");
    fclose($fpBig);
    $tempFilesToClean[] = $bigFile;
    $bigRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'video' => new CURLFile($bigFile, 'video/mp4', 'enorme.mp4'),
    ], ['Accept: application/json']);
    check('Rechaza video mayor al límite de 35 MB (413/422)', in_array($bigRes['status'], [413, 422], true), "HTTP {$bigRes['status']}");

    // 7. Carga válida de video real
    $realVideo = $ROOT . '/public/media/tour-propiedad.mp4';
    $vidUpRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'duracion_segundos' => '15.00',
        'ancho'             => '1280',
        'alto'              => '720',
        'video'             => new CURLFile($realVideo, 'video/mp4', 'mi_video_celular.mp4'),
    ], ['Accept: application/json']);
    check('Carga válida de MP4 real (200)', $vidUpRes['status'] === 200 && !empty($vidUpRes['json']['success']));
    $videoId = (int) ($vidUpRes['json']['video']['id'] ?? 0);
    $streamUrl = (string) ($vidUpRes['json']['video']['stream_url'] ?? '');

    $stmtV = $db->prepare('SELECT archivo_interno FROM recorrido_video WHERE id_video = ?');
    $stmtV->execute([$videoId]);
    $internalName = (string) $stmtV->fetchColumn();
    $createdVideoFile = $ROOT . '/storage/recorridos_videos/' . $internalName;
    check('Archivo de video guardado en storage privado con nombre aleatorio', str_starts_with($internalName, "vid_{$createdPropId}_") && is_file($createdVideoFile));

    // Verificar bloqueo directo por HTTP al storage privado
    $directVideoHttp = $guest->request('GET', $PROJECT_ROOT_URL . 'storage/recorridos_videos/' . rawurlencode($internalName));
    check('Acceso directo HTTP a storage/recorridos_videos bloqueado (403/404)', in_array($directVideoHttp['status'], [403, 404], true), "HTTP {$directVideoHttp['status']}");

    // 8. Streaming autenticado con HTTP Range 206
    $rangeOwner = $ownerA->request('GET', $streamUrl, null, ['Range: bytes=0-1023']);
    check('Stream de propietario responde 206 Partial Content (1024 bytes)', $rangeOwner['status'] === 206 && strlen($rangeOwner['body']) === 1024);

    // 9. Error comprensible si falta FFmpeg + limpieza de temporales
    $statusProc = $ownerA->request('GET', $BASE_URL . 'api/recorridos/procesador/estado');
    $ffmpegAvail = !empty($statusProc['json']['procesador']['ffmpeg_available']);
    if (!$ffmpegAvail) {
        $ffRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
            'video_id' => $videoId,
            'nombre' => 'Sala sin FFmpeg',
            'momento_segundos' => '1.5',
            'modo_extraccion' => 'ffmpeg',
        ], ['Accept: application/json']);
        check('Sin FFmpeg devuelve 503 con error claro', $ffRes['status'] === 503 && str_contains(strtolower($ffRes['json']['message'] ?? ''), 'ffmpeg'));
    } else {
        check('FFmpeg disponible en el servidor', true);
    }
    $leftoverTmp = glob($ROOT . '/storage/recorridos_tmp/*');
    $leftoverTmp = array_filter($leftoverTmp ?: [], fn($p) => !in_array(basename($p), ['.htaccess', '.gitkeep'], true));
    check('Carpeta temporal storage/recorridos_tmp limpia tras fallo (0 archivos)', count($leftoverTmp) === 0);

    // 10. Rechazo de fragmentos inválidos y archivo de imagen falsificado
    $badBounds1 = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
        'video_id' => $videoId,
        'nombre' => 'Sala inválida',
        'momento_segundos' => '2.0',
        'inicio_segundos' => '8.0',
        'fin_segundos' => '4.0',
        'modo_extraccion' => 'navegador',
        'frame_image' => new CURLFile($samplePhoto, 'image/jpeg', 'sala.jpg'),
    ], ['Accept: application/json']);
    check('Rechaza fragmento con fin <= inicio (422)', $badBounds1['status'] === 422);

    $badBounds2 = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
        'video_id' => $videoId,
        'nombre' => 'Sala fuera de rango',
        'momento_segundos' => '2.0',
        'inicio_segundos' => '1.0',
        'fin_segundos' => '999.0',
        'modo_extraccion' => 'navegador',
        'frame_image' => new CURLFile($samplePhoto, 'image/jpeg', 'sala.jpg'),
    ], ['Accept: application/json']);
    check('Rechaza fragmento que excede duración del video (422)', $badBounds2['status'] === 422);

    $badImgRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
        'video_id' => $videoId,
        'nombre' => 'Sala imagen falsa',
        'momento_segundos' => '2.0',
        'modo_extraccion' => 'navegador',
        'frame_image' => new CURLFile($fakePhp, 'image/jpeg', 'hack.jpg'),
    ], ['Accept: application/json']);
    check('Rechaza frame_image falsificado (422)', $badImgRes['status'] === 422);

    $leftoverTmp2 = array_filter(glob($ROOT . '/storage/recorridos_tmp/*') ?: [], fn($p) => !in_array(basename($p), ['.htaccess', '.gitkeep'], true));
    check('Carpeta temporal limpia tras rechazo de frame_image', count($leftoverTmp2) === 0);

    // 11. Extracción y creación válida de 2 escenas (Sala y Cocina)
    $extractMode = $ffmpegAvail ? 'ffmpeg' : 'navegador';
    $scene1Payload = [
        'video_id' => $videoId,
        'nombre' => 'Sala',
        'momento_segundos' => '1.50',
        'inicio_segundos' => '0.50',
        'fin_segundos' => '4.50',
        'modo_extraccion' => $extractMode,
    ];
    if (!$ffmpegAvail) {
        $scene1Payload['frame_image'] = new CURLFile($ROOT . '/public/tour/panoramas/sala.jpg', 'image/jpeg', 'sala.jpg');
    }
    $sc1Res = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", $scene1Payload, ['Accept: application/json']);
    check('Crear escena 1 (Sala) con fragmento válido (200)', $sc1Res['status'] === 200 && ($sc1Res['json']['escena']['tipo_visual'] ?? '') === 'imagen');
    $scene1Id = (int) ($sc1Res['json']['escena']['id'] ?? 0);
    $scene1Ruta = (string) ($sc1Res['json']['escena']['imagen_ruta'] ?? '');
    if ($scene1Ruta !== '') {
        $createdSceneFiles[] = $ROOT . '/public/' . $scene1Ruta;
    }
    check('Imagen real de escena 1 almacenada en public/uploads/recorridos/escenas', $scene1Ruta !== '' && @getimagesize($ROOT . '/public/' . $scene1Ruta) !== false);

    $scene2Payload = [
        'video_id' => $videoId,
        'nombre' => 'Cocina',
        'momento_segundos' => '6.00',
        'inicio_segundos' => '5.00',
        'fin_segundos' => '9.00',
        'modo_extraccion' => $extractMode,
    ];
    if (!$ffmpegAvail) {
        $scene2Payload['frame_image'] = new CURLFile($ROOT . '/public/tour/panoramas/comedor.jpg', 'image/jpeg', 'cocina.jpg');
    }
    $sc2Res = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", $scene2Payload, ['Accept: application/json']);
    check('Crear escena 2 (Cocina) con fragmento válido (200)', $sc2Res['status'] === 200);
    $scene2Id = (int) ($sc2Res['json']['escena']['id'] ?? 0);
    $scene2Ruta = (string) ($sc2Res['json']['escena']['imagen_ruta'] ?? '');
    if ($scene2Ruta !== '') {
        $createdSceneFiles[] = $ROOT . '/public/' . $scene2Ruta;
    }

    // Editar escena 2
    $updSc = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas/{$scene2Id}/actualizar", json_encode([
        'nombre' => 'Cocina integral',
        'inicio_segundos' => 5.5,
        'fin_segundos' => 9.5,
        'orden' => 2,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Actualizar nombre y fragmento de escena 2', $updSc['status'] === 200 && ($updSc['json']['escena']['nombre'] ?? '') === 'Cocina integral');

    // 12. Conexiones entre escenas y validaciones
    $selfConn = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/conexiones", json_encode([
        'escena_origen_id' => $scene1Id,
        'escena_destino_id' => $scene1Id,
        'etiqueta' => 'Bucle inválido',
        'posicion' => ['sistema' => 'plano_xy', 'x' => 50, 'y' => 50],
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Rechaza auto-conexión (origen == destino)', $selfConn['status'] === 422);

    $connRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/conexiones", json_encode([
        'escena_origen_id' => $scene1Id,
        'escena_destino_id' => $scene2Id,
        'etiqueta' => 'Ir a Cocina integral',
        'posicion' => ['sistema' => 'plano_xy', 'x' => 62.5, 'y' => 48.0],
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Crear conexión Sala -> Cocina integral', $connRes['status'] === 200 && !empty($connRes['json']['conexion']['id']));
    $connId = (int) ($connRes['json']['conexion']['id'] ?? 0);

    // 13. Aislamiento de propiedad: Cuenta B intenta acceder/modificar (incluso manipulando id_usuario/correo)
    $bStudio = $attackerB->request('GET', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido-video");
    check('Cuenta B no abre estudio de Cuenta A (404)', $bStudio['status'] === 404);
    $bData = $attackerB->request('GET', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video");
    check('Cuenta B no lee datos de estudio de Cuenta A (404)', $bData['status'] === 404);
    $bStream = $attackerB->request('GET', $streamUrl);
    check('Cuenta B no reproduce video privado de Cuenta A (404)', $bStream['status'] === 404);
    $bUpload = $attackerB->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'id_usuario' => $createdUserIds[0],
        'correo'     => $emailA,
        'video'      => new CURLFile($realVideo, 'video/mp4', 'intruso.mp4'),
    ], ['Accept: application/json']);
    check('Cuenta B no sube video a Cuenta A aunque manipule id_usuario/correo (404)', $bUpload['status'] === 404);
    $bScene = $attackerB->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas/{$scene1Id}/eliminar", json_encode([
        'id_usuario' => $createdUserIds[0],
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Cuenta B no elimina escena de Cuenta A (404)', $bScene['status'] === 404);
    $bConn = $attackerB->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/conexiones/{$connId}/eliminar", json_encode([]), ['Content-Type: application/json']);
    check('Cuenta B no elimina conexión de Cuenta A (404)', $bConn['status'] === 404);
    $bPub = $attackerB->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/estado", json_encode(['estado' => 'PUBLICADO']), ['Content-Type: application/json']);
    check('Cuenta B no publica recorrido de Cuenta A (404)', $bPub['status'] === 404);

    // 14. Borrador oculto a visitantes -> Publicar -> Visitante accede y reproduce fragmento
    $draftPublic = $guest->request('GET', $BASE_URL . "api/recorridos/{$createdPropId}");
    check('Recorrido en BORRADOR no es público (404)', $draftPublic['status'] === 404);
    $draftStream = $guest->request('GET', $BASE_URL . "api/recorridos/escenas/{$scene1Id}/video");
    check('Video de escena en BORRADOR no es público (404)', $draftStream['status'] === 404);

    $pubTourRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/estado", json_encode(['estado' => 'PUBLICADO']), ['Content-Type: application/json']);
    check('Propietario publica recorrido (200)', $pubTourRes['status'] === 200 && ($pubTourRes['json']['recorrido']['estado'] ?? '') === 'PUBLICADO');

    $visApi = $guest->request('GET', $BASE_URL . "api/recorridos/{$createdPropId}");
    check('Visitante obtiene contrato público con 2 escenas y 1 conexión', $visApi['status'] === 200 && count($visApi['json']['escenas'] ?? []) === 2 && count($visApi['json']['conexiones'] ?? []) === 1);
    $visPage = $guest->request('GET', $BASE_URL . "recorrido/{$createdPropId}");
    check('Visitante abre visor HTML /recorrido/{id} (200)', $visPage['status'] === 200 && str_contains($visPage['body'], 'RECORRIDO_VISOR_INIT'));
    $visFrag = $guest->request('GET', $BASE_URL . "api/recorridos/escenas/{$scene1Id}/video", null, ['Range: bytes=0-2047']);
    check('Visitante reproduce fragmento de escena por Range 206 (2048 bytes)', $visFrag['status'] === 206 && strlen($visFrag['body']) === 2048);

    $catProp = $guest->request('GET', $BASE_URL . "api/propiedades/{$createdPropId}");
    check('API catálogo expone sceneTour e isSceneTour', $catProp['status'] === 200 && !empty($catProp['json']['virtualTour']['isSceneTour']) && (int) ($catProp['json']['sceneTour']['total_escenas'] ?? 0) === 2);

    // 15. Persistencia después de cerrar y volver a iniciar sesión
    $ownerA->request('POST', $BASE_URL . 'auth/logout', [], ['Accept: application/json']);
    $ownerA->refreshCsrf($BASE_URL);
    $loginRes = $ownerA->request('POST', $BASE_URL . 'auth/login', http_build_query([
        'email' => $emailA,
        'password' => $pwd,
    ]), ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']);
    check('Propietario vuelve a iniciar sesión', $loginRes['status'] === 200 && !empty($loginRes['json']['success']));
    if (!empty($loginRes['json']['csrf_token'])) {
        $ownerA->csrf = $loginRes['json']['csrf_token'];
    }
    $afterLogin = $ownerA->request('GET', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video");
    check('Videos, escenas y conexiones persisten tras relogin', $afterLogin['status'] === 200 && count($afterLogin['json']['videos'] ?? []) === 1 && count($afterLogin['json']['escenas'] ?? []) === 2 && count($afterLogin['json']['conexiones'] ?? []) === 1);

    // 16. Bloqueo de acceso directo HTTP a .htaccess en carpeta de cargas
    $htaccessCheck = $guest->request('GET', $BASE_URL . 'uploads/recorridos/escenas/.htaccess');
    check('Acceso HTTP a .htaccess de cargas bloqueado (403/404)', in_array($htaccessCheck['status'], [403, 404], true));
} finally {
    // Limpieza total de archivos temporales y fixtures de BD
    foreach ($tempFilesToClean as $tf) {
        if (is_file($tf)) {
            @unlink($tf);
        }
    }
    if ($createdVideoFile && is_file($createdVideoFile)) {
        @unlink($createdVideoFile);
    }
    foreach ($createdSceneFiles as $sf) {
        if (is_file($sf)) {
            @unlink($sf);
        }
    }
    if ($createdPropId > 0) {
        $imgStmt = $db->prepare('SELECT url_imagen FROM imagen_propiedad WHERE id_propiedad = ?');
        $imgStmt->execute([$createdPropId]);
        foreach ($imgStmt->fetchAll(PDO::FETCH_COLUMN) as $imgRel) {
            $p = $ROOT . '/public/' . ltrim((string) $imgRel, '/');
            if (is_file($p)) {
                @unlink($p);
            }
        }
        $db->prepare('DELETE FROM propiedad WHERE id_propiedad = ?')->execute([$createdPropId]);
    }
    foreach ($createdUserIds as $uid) {
        if ($uid > 0) {
            $db->prepare('DELETE FROM usuario WHERE id_usuario = ?')->execute([$uid]);
        }
    }
}

echo "\nResumen: {$passed} pasaron, {$failed} fallaron.\n";
exit($failed > 0 ? 1 : 0);

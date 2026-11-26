<?php
/**
 * Suite de pruebas integrales del módulo unificado de recorridos
 * (fotografías, panorámicas horizontales y escenas desde video — INFONATEC).
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
$trackedVideoFiles = [];
$trackedArchiveFiles = [];

try {
    $guest = new HttpClient();
    $ownerA = new HttpClient();
    $attackerB = new HttpClient();

    // 1. Regresión de rutas públicas y tours 360 existentes
    foreach (['', 'explorar', 'vender', 'api/propiedades', 'tour?prop=2', 'mis-propiedades'] as $route) {
        $r = $guest->request('GET', $BASE_URL . $route);
        check("Regresión ruta /{$route}", $r['status'] === 200, "HTTP {$r['status']}");
    }
    $prop2 = $guest->request('GET', $BASE_URL . 'api/propiedades/2');
    check(
        'Propiedad 2 conserva tour 360 existente intacto',
        $prop2['status'] === 200
            && !empty($prop2['json']['virtualTour']['url'])
            && !str_starts_with((string) $prop2['json']['virtualTour']['url'], 'recorridos/')
    );

    // 2. Registrar dos cuentas temporales y publicar propiedad con Cuenta A
    $pwd = bin2hex(random_bytes(8)) . 'A1!';
    $emailA = 'qa_uni_a_' . bin2hex(random_bytes(4)) . '@example.invalid';
    $emailB = 'qa_uni_b_' . bin2hex(random_bytes(4)) . '@example.invalid';

    foreach ([['A', $ownerA, $emailA], ['B', $attackerB, $emailB]] as [$lbl, $client, $email]) {
        $client->refreshCsrf($BASE_URL);
        $r = $client->request('POST', $BASE_URL . 'auth/register', http_build_query([
            'name'     => 'Propietario',
            'last'     => 'Prueba ' . $lbl,
            'email'    => $email,
            'phone'    => '9511112233',
            'password' => $pwd,
        ]), ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']);
        check("Registrar cuenta temporal {$lbl}", $r['status'] === 200 && !empty($r['json']['success']));
        $createdUserIds[] = (int) ($r['json']['user']['id'] ?? 0);
        if (!empty($r['json']['csrf_token'])) {
            $client->csrf = $r['json']['csrf_token'];
        }
    }

    $samplePhoto = $ROOT . '/public/tour/panoramas/industrial/lote.jpg';
    $samplePano = $ROOT . '/public/tour/panoramas/sala.jpg';
    $realVideo = $ROOT . '/public/media/tour-propiedad.mp4';

    $pubRes = $ownerA->request('POST', $BASE_URL . 'vender/publicar', [
        'title'       => 'Casa QA Recorrido Unificado',
        'type'        => 'CASA',
        'operation'   => 'VENTA',
        'price'       => '3200000',
        'location'    => 'Centro, Oaxaca',
        'description' => 'Propiedad temporal para pruebas de recorrido mixto fotos + panorámicas + video.',
        'area'        => '180',
        'bedrooms'    => '3',
        'bathrooms'   => '2',
        'owner_phone' => '9511112233',
        'photos[0]'   => new CURLFile($samplePhoto, 'image/jpeg', 'lote.jpg'),
    ], ['Accept: application/json']);
    check('Publicar propiedad de prueba (Cuenta A)', $pubRes['status'] === 200 && !empty($pubRes['json']['success']));
    $createdPropId = (int) ($pubRes['json']['id'] ?? 0);

    // Verificar aviso en /vender
    $venderHtml = $ownerA->request('GET', $BASE_URL . 'vender');
    check('Vender incluye acceso directo a Diseñar recorrido tras publicar', $venderHtml['status'] === 200 && str_contains($venderHtml['body'], 'publishTourLink'));

    // 3. Control de acceso no autenticado y editor unificado
    check('Invitado recibe 401 en API privada de videos', $guest->request('GET', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos")['status'] === 401);
    check('Invitado recibe 401 en editor /recorrido', $guest->request('GET', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido")['status'] === 401);
    check('Invitado recibe 401 en estado de procesador', $guest->request('GET', $BASE_URL . 'api/recorridos/procesador/estado')['status'] === 401);
    check('Otra cuenta no abre editor unificado (404)', $attackerB->request('GET', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido")['status'] === 404);

    $editorRes = $ownerA->request('GET', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido");
    check('Propietario abre editor unificado con estudio de video integrado (200)', $editorRes['status'] === 200 && str_contains($editorRes['body'], 'ptVideoPanel') && str_contains($editorRes['body'], 'ptBoot'));

    $redirEd = $ownerA->request('GET', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido-video");
    check('Ruta /recorrido-video redirige al editor compartido (302)', $redirEd['status'] === 302 && str_contains($redirEd['headers'], "mis-propiedades/{$createdPropId}/recorrido#video"));

    // 4. Flujo de fotografías y panorámicas (módulo compañero)
    $noCsrfFoto = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/foto", [
        'foto' => new CURLFile($samplePhoto, 'image/jpeg', 'foto.jpg'),
    ], ['Accept: application/json'], false);
    check('POST foto sin CSRF rechazado (403)', $noCsrfFoto['status'] === 403);

    $upFoto = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/foto", [
        'foto' => new CURLFile($samplePhoto, 'image/jpeg', 'lote.jpg'),
    ], ['Accept: application/json']);
    check('Carga válida de fotografía normal (200)', $upFoto['status'] === 200 && !empty($upFoto['json']['success']));
    $photoScene = $upFoto['json']['scene'];
    $photoScene['nombre'] = 'Fachada';
    $photoScene['tipo_visual'] = 'imagen';
    $photoId = (int) $photoScene['id'];

    $upPano = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/foto", [
        'foto' => new CURLFile($samplePano, 'image/jpeg', 'sala.jpg'),
    ], ['Accept: application/json']);
    check('Carga válida de fotografía panorámica (200)', $upPano['status'] === 200 && !empty($upPano['json']['success']));
    $panoScene = $upPano['json']['scene'];
    $panoScene['nombre'] = 'Mirador panorámico';
    $panoScene['tipo_visual'] = 'panorama';
    $panoScene['angulo_horizontal'] = 360;
    $panoId = (int) $panoScene['id'];

    // Registrar archivos creados para limpieza garantizada
    $qArch = $db->prepare('SELECT a.ruta FROM recorrido_archivo a JOIN recorrido_foto r ON r.id_recorrido=a.id_recorrido WHERE r.id_propiedad=?');
    $qArch->execute([$createdPropId]);
    foreach ($qArch->fetchAll(PDO::FETCH_COLUMN) as $ar) {
        $trackedArchiveFiles[] = $ROOT . '/storage/recorridos/' . basename((string) $ar);
    }

    check('Imagen en borrador privada para visitante (404)', $guest->request('GET', $BASE_URL . "recorridos/imagen/{$photoId}")['status'] === 404);
    check('Imagen en borrador privada para Cuenta B (404)', $attackerB->request('GET', $BASE_URL . "recorridos/imagen/{$photoId}")['status'] === 404);
    check('Propietario ve su imagen en borrador (200)', $ownerA->request('GET', $BASE_URL . "recorridos/imagen/{$photoId}")['status'] === 200);

    // Validaciones del grafo en /guardar
    $badAngleGraph = ['schema_version' => 1, 'escena_inicial_id' => $panoId, 'escenas' => [$panoScene], 'conexiones' => []];
    $badAngleGraph['escenas'][0]['angulo_horizontal'] = 123;
    $badAngleRes = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 0, 'accion' => 'guardar', 'graph' => $badAngleGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Rechaza cobertura angular inválida en panorámica (400)', $badAngleRes['status'] === 400);

    $discGraph = ['schema_version' => 1, 'escena_inicial_id' => $photoId, 'escenas' => [$photoScene, $panoScene], 'conexiones' => []];
    $discRes = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 0, 'accion' => 'publicar', 'graph' => $discGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Rechaza publicar escenas desconectadas (400)', $discRes['status'] === 400);

    $staleRes = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 99, 'accion' => 'guardar', 'graph' => $discGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Detecta conflicto de versión entre pestañas (409)', $staleRes['status'] === 409);

    // 5. Estado procesador de video + CSRF + rechazo archivos inválidos/grandes
    $procRes = $ownerA->request('GET', $BASE_URL . 'api/recorridos/procesador/estado');
    check('Estado procesador responde 200', $procRes['status'] === 200 && isset($procRes['json']['procesador']));
    $ffmpegAvail = !empty($procRes['json']['procesador']['ffmpeg_available']);

    $noCsrfVid = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [], ['Accept: application/json'], false);
    check('POST video sin CSRF rechazado (403)', $noCsrfVid['status'] === 403 && ($noCsrfVid['json']['error'] ?? '') === 'csrf_invalid');

    $noCsrfExtract = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [], ['Accept: application/json'], false);
    check('POST extraer escena sin CSRF rechazado (403)', $noCsrfExtract['status'] === 403);

    $missFile = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", ['nota' => 'sin archivo'], ['Accept: application/json']);
    check('Rechaza carga de video sin archivo (400)', $missFile['status'] === 400);

    $fakePhp = tempnam(sys_get_temp_dir(), 'fake_');
    file_put_contents($fakePhp, "<?php echo 'exploit'; ?>");
    $tempFilesToClean[] = $fakePhp;
    $badPhpVid = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'video' => new CURLFile($fakePhp, 'video/mp4', 'malicioso.mp4'),
    ], ['Accept: application/json']);
    check('Rechaza PHP disfrazado de MP4 (400)', $badPhpVid['status'] === 400);

    $txtFile = tempnam(sys_get_temp_dir(), 'txt_');
    file_put_contents($txtFile, 'Texto plano no multimedia');
    $tempFilesToClean[] = $txtFile;
    $badTxtVid = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'video' => new CURLFile($txtFile, 'text/plain', 'nota.txt'),
    ], ['Accept: application/json']);
    check('Rechaza archivo de texto inválido (400)', $badTxtVid['status'] === 400);

    $bigFile = tempnam(sys_get_temp_dir(), 'big_');
    $fpBig = fopen($bigFile, 'wb');
    fseek($fpBig, (36 * 1048576) - 1);
    fwrite($fpBig, "\0");
    fclose($fpBig);
    $tempFilesToClean[] = $bigFile;
    $bigVid = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'video' => new CURLFile($bigFile, 'video/mp4', 'enorme.mp4'),
    ], ['Accept: application/json']);
    check('Rechaza video mayor a 35 MB (400/413)', in_array($bigVid['status'], [400, 413], true), "HTTP {$bigVid['status']}");

    // 6. Carga válida de MP4 real y verificación en almacenamiento protegido
    $vidUpRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'duracion_segundos' => '15.00',
        'ancho'             => '1280',
        'alto'              => '720',
        'video'             => new CURLFile($realVideo, 'video/mp4', 'mi_video_celular.mp4'),
    ], ['Accept: application/json']);
    check('Carga válida de MP4 real (201)', $vidUpRes['status'] === 201 && !empty($vidUpRes['json']['success']));
    $videoId = (int) ($vidUpRes['json']['video']['id'] ?? 0);
    $streamUrl = (string) ($vidUpRes['json']['video']['stream_url'] ?? '');

    $stmtV = $db->prepare('SELECT archivo_interno FROM recorrido_video WHERE id_video = ?');
    $stmtV->execute([$videoId]);
    $internalVidName = (string) $stmtV->fetchColumn();
    if ($internalVidName !== '') {
        $trackedVideoFiles[] = $ROOT . '/storage/recorridos_videos/' . $internalVidName;
    }
    check('Video almacenado con nombre aleatorio en storage/recorridos_videos', str_starts_with($internalVidName, "vid_{$createdPropId}_") && is_file($ROOT . '/storage/recorridos_videos/' . $internalVidName));

    // Bloqueo directo HTTP a storage
    foreach (['storage/recorridos_videos/' . rawurlencode($internalVidName), 'storage/recorridos/', 'storage/recorridos_tmp/'] as $stPath) {
        $stCheck = $guest->request('GET', $PROJECT_ROOT_URL . $stPath);
        check("Acceso directo HTTP bloqueado para {$stPath} (403/404)", in_array($stCheck['status'], [403, 404], true), "HTTP {$stCheck['status']}");
    }

    // Streaming propietario Range 206 + rechazo invitado/ajeno
    $rangeOwner = $ownerA->request('GET', $streamUrl, null, ['Range: bytes=0-1023']);
    check('Streaming propietario responde 206 Partial Content (1024 bytes)', $rangeOwner['status'] === 206 && strlen($rangeOwner['body']) === 1024);
    check('Invitado no reproduce stream privado de propietario (401)', $guest->request('GET', $streamUrl)['status'] === 401);
    check('Cuenta B no reproduce stream privado de Cuenta A (404)', $attackerB->request('GET', $streamUrl)['status'] === 404);

    // 7. Extracción de escenas: FFmpeg ausente (503 + 0 temporales), validaciones, frame navegador
    if (!$ffmpegAvail) {
        $ffRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
            'video_id'         => $videoId,
            'nombre'           => 'Sala sin FFmpeg',
            'momento_segundos' => '1.5',
            'modo_extraccion'  => 'ffmpeg',
        ], ['Accept: application/json']);
        check('Sin FFmpeg devuelve 503 con mensaje comprensible', $ffRes['status'] === 503 && str_contains(strtolower($ffRes['json']['message'] ?? ''), 'ffmpeg'));
    } else {
        check('FFmpeg disponible en el servidor', true);
    }
    $leftTmp1 = array_filter(glob($ROOT . '/storage/recorridos_tmp/*') ?: [], fn($p) => !in_array(basename($p), ['.htaccess', '.gitkeep'], true));
    check('Carpeta temporal storage/recorridos_tmp limpia (0 archivos)', count($leftTmp1) === 0);

    $badFrame = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
        'video_id'         => $videoId,
        'nombre'           => 'Sala frame falso',
        'momento_segundos' => '2.0',
        'modo_extraccion'  => 'navegador',
        'frame'            => new CURLFile($fakePhp, 'image/jpeg', 'hack.jpg'),
    ], ['Accept: application/json']);
    check('Rechaza frame falsificado desde navegador (400)', $badFrame['status'] === 400);

    $badFrag1 = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
        'video_id'         => $videoId,
        'nombre'           => 'Sala fin<=inicio',
        'momento_segundos' => '2.0',
        'inicio_segundos'  => '8.0',
        'fin_segundos'     => '4.0',
        'modo_extraccion'  => 'navegador',
        'frame'            => new CURLFile($samplePhoto, 'image/jpeg', 'frame.jpg'),
    ], ['Accept: application/json']);
    check('Rechaza fragmento con fin <= inicio (400)', $badFrag1['status'] === 400);

    $badFrag2 = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", [
        'video_id'         => $videoId,
        'nombre'           => 'Sala fuera duración',
        'momento_segundos' => '2.0',
        'inicio_segundos'  => '1.0',
        'fin_segundos'     => '999.0',
        'modo_extraccion'  => 'navegador',
        'frame'            => new CURLFile($samplePhoto, 'image/jpeg', 'frame.jpg'),
    ], ['Accept: application/json']);
    check('Rechaza fragmento fuera de duración del video (400)', $badFrag2['status'] === 400);

    $extractMode = $ffmpegAvail ? 'ffmpeg' : 'navegador';
    $extPayload = [
        'video_id'         => $videoId,
        'nombre'           => 'Sala video',
        'momento_segundos' => '1.50',
        'inicio_segundos'  => '0.50',
        'fin_segundos'     => '3.50',
        'modo_extraccion'  => $extractMode,
    ];
    if (!$ffmpegAvail) {
        $extPayload['frame'] = new CURLFile($ROOT . '/public/tour/panoramas/sala.jpg', 'image/jpeg', 'sala.jpg');
    }
    $extRes = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", $extPayload, ['Accept: application/json']);
    check('Extraer escena válida desde video en almacén unificado (201)', $extRes['status'] === 201 && !empty($extRes['json']['scene']['id']) && (int) ($extRes['json']['scene']['video_id'] ?? 0) === $videoId);
    $videoScene = $extRes['json']['scene'];
    $videoSceneId = (int) $videoScene['id'];

    $qArch->execute([$createdPropId]);
    foreach ($qArch->fetchAll(PDO::FETCH_COLUMN) as $ar) {
        $trackedArchiveFiles[] = $ROOT . '/storage/recorridos/' . basename((string) $ar);
    }

    // 8. Validaciones de procedencia de video dentro de RecorridoFoto::save()
    $badVidIdGraph = ['schema_version' => 1, 'escena_inicial_id' => $videoSceneId, 'escenas' => [$videoScene], 'conexiones' => []];
    $badVidIdGraph['escenas'][0]['video_id'] = 999999;
    $resBadVid = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 0, 'accion' => 'guardar', 'graph' => $badVidIdGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('RecorridoFoto rechaza video_id ajeno o inexistente (400)', $resBadVid['status'] === 400);

    $badPanoVidGraph = ['schema_version' => 1, 'escena_inicial_id' => $videoSceneId, 'escenas' => [$videoScene], 'conexiones' => []];
    $badPanoVidGraph['escenas'][0]['tipo_visual'] = 'panorama';
    $resPanoVid = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 0, 'accion' => 'guardar', 'graph' => $badPanoVidGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('RecorridoFoto rechaza tipo panorama en escena de video (400)', $resPanoVid['status'] === 400);

    $badFragSaveGraph = ['schema_version' => 1, 'escena_inicial_id' => $videoSceneId, 'escenas' => [$videoScene], 'conexiones' => []];
    $badFragSaveGraph['escenas'][0]['inicio_segundos'] = 5.0;
    $badFragSaveGraph['escenas'][0]['fin_segundos'] = 2.0;
    $resFragSave = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 0, 'accion' => 'guardar', 'graph' => $badFragSaveGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('RecorridoFoto rechaza fin_segundos <= inicio_segundos (400)', $resFragSave['status'] === 400);

    // 9. Recorrido mixto (Foto + Panorámica + Video) conectado en borrador -> preview -> publicación
    $mixedGraph = [
        'schema_version'    => 1,
        'escena_inicial_id' => $photoId,
        'escenas'           => [$photoScene, $panoScene, $videoScene],
        'conexiones'        => [
            [
                'escena_origen_id'  => $photoId,
                'escena_destino_id' => $panoId,
                'etiqueta'          => 'Ir al mirador panorámico',
                'coordenadas'       => 'imagen_normalizada',
                'x'                 => 0.35,
                'y'                 => 0.50,
            ],
            [
                'escena_origen_id'  => $panoId,
                'escena_destino_id' => $videoSceneId,
                'etiqueta'          => 'Ir a Sala (video)',
                'coordenadas'       => 'imagen_normalizada',
                'x'                 => 0.72,
                'y'                 => 0.55,
            ],
        ],
    ];

    $saveDraftRes = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 0, 'accion' => 'guardar', 'graph' => $mixedGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Guarda borrador de recorrido mixto foto+panorámica+video (version=1)', $saveDraftRes['status'] === 200 && (int) ($saveDraftRes['json']['version'] ?? 0) === 1);

    check('Borrador oculto a visitante en /recorridos/{id} (404)', $guest->request('GET', $BASE_URL . "recorridos/{$createdPropId}")['status'] === 404);
    check('Video de escena en borrador oculto a visitante (404)', $guest->request('GET', $BASE_URL . "api/recorridos/{$createdPropId}/escenas/{$videoSceneId}/video")['status'] === 404);
    check('Vista previa de borrador rechazada a Cuenta B (404)', $attackerB->request('GET', $BASE_URL . "recorridos/{$createdPropId}?preview=1")['status'] === 404);
    check('Video de preview rechazado a Cuenta B (404)', $attackerB->request('GET', $BASE_URL . "api/recorridos/{$createdPropId}/escenas/{$videoSceneId}/video?preview=1")['status'] === 404);

    $ownerPrevPage = $ownerA->request('GET', $BASE_URL . "recorridos/{$createdPropId}?preview=1");
    $ownerPrevVid = $ownerA->request('GET', $BASE_URL . "api/recorridos/{$createdPropId}/escenas/{$videoSceneId}/video?preview=1", null, ['Range: bytes=0-2047']);
    check('Propietario accede a vista previa de borrador y video fragmentado (200 + 206)', $ownerPrevPage['status'] === 200 && $ownerPrevVid['status'] === 206 && strlen($ownerPrevVid['body']) === 2048);

    // Publicar recorrido mixto
    $pubTour = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 1, 'accion' => 'publicar', 'graph' => $mixedGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Publicar recorrido mixto conectado (version=2)', $pubTour['status'] === 200 && !empty($pubTour['json']['published']) && (int) ($pubTour['json']['version'] ?? 0) === 2);

    $catProp = $guest->request('GET', $BASE_URL . "api/propiedades/{$createdPropId}");
    check('Ficha de catálogo apunta a recorridos/{id}', $catProp['status'] === 200 && ($catProp['json']['virtualTour']['url'] ?? '') === "recorridos/{$createdPropId}");

    $visTour = $guest->request('GET', $BASE_URL . "recorridos/{$createdPropId}");
    check('Visitante abre visor público con escenas de foto, panorámica y video (200)', $visTour['status'] === 200 && str_contains($visTour['body'], 'Sala video') && str_contains($visTour['body'], 'Mirador panorámico'));
    $visOldRoute = $guest->request('GET', $BASE_URL . "recorrido/{$createdPropId}");
    check('Ruta /recorrido/{id} redirige al visor unificado (302)', $visOldRoute['status'] === 302 && str_contains($visOldRoute['headers'], "recorridos/{$createdPropId}"));
    check('Visitante carga imagen extraída de escena de video (200)', $guest->request('GET', $BASE_URL . "recorridos/imagen/{$videoSceneId}")['status'] === 200);
    $visFrag = $guest->request('GET', $BASE_URL . "api/recorridos/{$createdPropId}/escenas/{$videoSceneId}/video", null, ['Range: bytes=0-2047']);
    check('Visitante reproduce fragmento de escena de video por Range 206 (2048 bytes)', $visFrag['status'] === 206 && strlen($visFrag['body']) === 2048);

    // 10. Integridad: no eliminar video en uso + aislamiento Cuenta B + relogin
    $delInUse = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos/{$videoId}/eliminar", json_encode([]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Rechaza eliminar video en uso por escenas guardadas/publicadas (409)', $delInUse['status'] === 409);

    $bUploadSpoof = $attackerB->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos", [
        'id_usuario' => $createdUserIds[0],
        'correo'     => $emailA,
        'video'      => new CURLFile($realVideo, 'video/mp4', 'intruso.mp4'),
    ], ['Accept: application/json']);
    check('Cuenta B no sube video a Cuenta A aunque manipule id_usuario/correo (404)', $bUploadSpoof['status'] === 404);
    check('Cuenta B no extrae escena en propiedad de Cuenta A (404)', $attackerB->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/escenas", ['video_id' => $videoId, 'nombre' => 'Intruso', 'momento_segundos' => '1', 'modo_extraccion' => 'navegador', 'frame' => new CURLFile($samplePhoto, 'image/jpeg', 'x.jpg')], ['Accept: application/json'])['status'] === 404);
    check('Cuenta B no guarda ni oculta recorrido de Cuenta A (404)', $attackerB->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode(['version' => 2, 'accion' => 'ocultar', 'graph' => $mixedGraph]), ['Content-Type: application/json'])['status'] === 404);

    // Persistencia tras cerrar y volver a iniciar sesión
    $ownerA->request('POST', $BASE_URL . 'auth/logout', [], ['Accept: application/json']);
    $ownerA->refreshCsrf($BASE_URL);
    $loginRes = $ownerA->request('POST', $BASE_URL . 'auth/login', http_build_query([
        'email'    => $emailA,
        'password' => $pwd,
    ]), ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']);
    check('Propietario vuelve a iniciar sesión', $loginRes['status'] === 200 && !empty($loginRes['json']['success']));
    if (!empty($loginRes['json']['csrf_token'])) {
        $ownerA->csrf = $loginRes['json']['csrf_token'];
    }
    $afterReloginEd = $ownerA->request('GET', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido");
    $afterReloginVid = $ownerA->request('GET', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos");
    check('Escenas mixtas y video persisten después de cerrar y reabrir sesión', $afterReloginEd['status'] === 200 && str_contains($afterReloginEd['body'], 'Sala video') && count($afterReloginVid['json']['videos'] ?? []) === 1);

    // 11. Ocultar revoca visor, imágenes y video público manteniendo borrador -> desvincular, eliminar video y limpiar
    $hideRes = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 2, 'accion' => 'ocultar', 'graph' => $mixedGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Ocultar recorrido responde 200 (published=false, version=3)', $hideRes['status'] === 200 && empty($hideRes['json']['published']) && (int) ($hideRes['json']['version'] ?? 0) === 3);
    check('Ocultar bloquea visor público (404)', $guest->request('GET', $BASE_URL . "recorridos/{$createdPropId}")['status'] === 404);
    check('Ocultar bloquea imagen pública (404)', $guest->request('GET', $BASE_URL . "recorridos/imagen/{$videoSceneId}")['status'] === 404);
    check('Ocultar bloquea stream de video público (404)', $guest->request('GET', $BASE_URL . "api/recorridos/{$createdPropId}/escenas/{$videoSceneId}/video")['status'] === 404);

    // Quitar escena de video del borrador para poder eliminar el video y limpiar su imagen
    $unlinkedGraph = [
        'schema_version'    => 1,
        'escena_inicial_id' => $photoId,
        'escenas'           => [$photoScene, $panoScene],
        'conexiones'        => [[
            'escena_origen_id'  => $photoId,
            'escena_destino_id' => $panoId,
            'etiqueta'          => 'Ir al mirador',
            'coordenadas'       => 'imagen_normalizada',
            'x'                 => 0.5,
            'y'                 => 0.5,
        ]],
    ];
    $saveUnlink = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/guardar", json_encode([
        'version' => 3, 'accion' => 'guardar', 'graph' => $unlinkedGraph,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Desvincular escena de video en borrador (version=4)', $saveUnlink['status'] === 200 && (int) ($saveUnlink['json']['version'] ?? 0) === 4);

    $delUnusedVid = $ownerA->request('POST', $BASE_URL . "api/mis-propiedades/{$createdPropId}/recorrido-video/videos/{$videoId}/eliminar", json_encode([]), ['Content-Type: application/json', 'Accept: application/json']);
    $vidPathAfterDel = $ROOT . '/storage/recorridos_videos/' . $internalVidName;
    clearstatcache(true, $vidPathAfterDel);
    check('Eliminar video sin uso responde 200 y borra archivo físico', $delUnusedVid['status'] === 200 && !is_file($vidPathAfterDel), "HTTP {$delUnusedVid['status']} exists=" . (is_file($vidPathAfterDel) ? '1' : '0'));

    $cleanRes = $ownerA->request('POST', $BASE_URL . "mis-propiedades/{$createdPropId}/recorrido/limpiar", json_encode([
        'version' => 4,
    ]), ['Content-Type: application/json', 'Accept: application/json']);
    check('Limpiar imágenes sin usar elimina el fotograma desvinculado', $cleanRes['status'] === 200 && (int) ($cleanRes['json']['deleted'] ?? 0) >= 1 && $ownerA->request('GET', $BASE_URL . "recorridos/imagen/{$videoSceneId}")['status'] === 404);
} finally {
    foreach ($tempFilesToClean as $tf) {
        if (is_file($tf)) {
            @unlink($tf);
        }
    }
    foreach ($trackedVideoFiles as $vf) {
        if (is_file($vf)) {
            @unlink($vf);
        }
    }
    foreach ($trackedArchiveFiles as $af) {
        if (is_file($af)) {
            @unlink($af);
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

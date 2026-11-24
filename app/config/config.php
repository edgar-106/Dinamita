<?php
/**
 * Configuración global del sistema INFONATEC
 */

require_once __DIR__ . '/env.php';

// Entorno de ejecución ('development' o 'production')
define('APP_ENV', env('APP_ENV', 'development'));

// Construimos la URL base desde la petición actual. Esto permite ejecutar el
// proyecto tanto en XAMPP (/Bienes%20raices/public/) como en un dominio real.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// SCRIPT_NAME apunta al front controller; de él obtenemos el prefijo que el
// router debe retirar antes de comparar rutas.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/') . '/';
if ($basePath === '//' || $basePath === '.') {
    $basePath = '/';
}
$encodedBasePath = str_replace(' ', '%20', $basePath);

// Configuración segura de la sesión antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $encodedBasePath,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Expiración de sesión por inactividad (30 minutos)
$sessionTimeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $sessionTimeout) {
    unset($_SESSION['user'], $_SESSION['csrf_token']);
}
$_SESSION['last_activity'] = time();

// BASE_PATH se usa para enrutar (decodificado); BASE_URL para generar enlaces y recursos.
define('BASE_PATH', $basePath);
define('BASE_URL', $protocol . $host . $encodedBasePath);
define('APP_NAME', 'INFONATEC');
define('APP_TITLE', 'INFONATEC | Tu hogar, tu nido');

// Credenciales y configuración cargadas desde variables de entorno (.env)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'sistema_inmobiliario'));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
define('ADMIN_EMAIL', strtolower(trim((string) env('ADMIN_EMAIL', '123@gmail.com'))));

<?php
/**
 * Configuración global del sistema INFONATEC
 */

// La sesión se inicia en un único lugar para que controladores y vistas puedan
// consultar al usuario autenticado sin reiniciarla en cada petición.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Construimos la URL base desde la petición actual. Esto permite ejecutar el
// proyecto tanto en XAMPP (/infonatec/public/) como en un dominio real.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// SCRIPT_NAME apunta al front controller; de él obtenemos el prefijo que el
// router debe retirar antes de comparar rutas.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/') . '/';
if ($basePath === '//') {
    $basePath = '/';
}

// BASE_PATH se usa para enrutar; BASE_URL para generar enlaces y recursos.
define('BASE_PATH', $basePath);
define('BASE_URL', $protocol . $host . $basePath);
define('APP_NAME', 'INFONATEC');
define('APP_TITLE', 'INFONATEC | Tu hogar, tu nido');

// Credenciales locales por defecto. En producción deben sustituirse mediante
// variables de entorno o un archivo de configuración fuera del repositorio.
define('DB_HOST', 'localhost');
define('DB_NAME', 'infonatec_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

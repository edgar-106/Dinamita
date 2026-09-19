<?php
/**
 * Configuración global del sistema INFONATEC
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detección dinámica del protocolo y host
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Determinar el directorio base del proyecto (útil en XAMPP p.ej. /infonatec/)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/') . '/';
if ($basePath === '//') {
    $basePath = '/';
}

define('BASE_PATH', $basePath);
define('BASE_URL', $protocol . $host . $basePath);
define('APP_NAME', 'INFONATEC');
define('APP_TITLE', 'INFONATEC | Tu hogar, tu nido');

// Configuración de Base de Datos (MySQL / MariaDB por defecto en XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'infonatec_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

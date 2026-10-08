<?php
/**
 * Router exclusivo de desarrollo para `php -S`.
 * Apache utiliza public/.htaccess; el servidor integrado necesita este archivo
 * para enviar rutas dinámicas al front controller sin interceptar recursos.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$publicPath = dirname(__DIR__) . '/public';
$file = $publicPath . $path;

// CSS, JS, imágenes y videos se sirven directamente para imitar a Apache.
if ($path !== '/' && is_file($file)) {
    return false;
}

// Las rutas que no son archivos pasan por el router MVC.
require $publicPath . '/index.php';

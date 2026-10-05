<?php
// Router temporal para validar las rutas MVC con el servidor integrado de PHP.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$publicPath = dirname(__DIR__) . '/public';
$file = $publicPath . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require $publicPath . '/index.php';

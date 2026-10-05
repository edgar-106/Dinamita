<?php
/**
 * Inicialización única de la aplicación.
 *
 * Mantiene la configuración y la carga de clases fuera de los controladores,
 * para que el punto de entrada y los endpoints de compatibilidad usen la
 * misma base MVC.
 */

require_once __DIR__ . '/config/config.php';

spl_autoload_register(static function (string $class): void {
    $directories = ['core', 'models', 'controllers', 'config'];

    foreach ($directories as $directory) {
        $file = __DIR__ . '/' . $directory . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

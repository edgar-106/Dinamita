<?php
/**
 * Inicialización única de la aplicación.
 *
 * Mantiene la configuración y la carga de clases fuera de los controladores,
 * para que el punto de entrada y los endpoints de compatibilidad usen la
 * misma base MVC.
 */

// Primero se cargan las constantes y la sesión, requeridas por todas las capas.
require_once __DIR__ . '/config/config.php';

// Carga bajo demanda las clases propias. Mantener esta lista explícita evita
// buscar archivos fuera de la estructura controlada de la aplicación.
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

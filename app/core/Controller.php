<?php
/**
 * Controlador Base del patrón MVC
 */

class Controller {
    /**
     * Renderiza una vista dentro del layout principal (header + view + footer)
     *
     * @param string $view Ruta relativa de la vista dentro de app/views/ (ej: 'home/index')
     * @param array $data Datos pasados a la vista
     * @param bool $withLayout Indica si se debe incluir el layout estándar (header y footer)
     */
    public function renderView(string $view, array $data = [], bool $withLayout = true): void {
        // Extraer variables para que estén disponibles directamente en la vista
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo "<h1>Error 404</h1><p>Vista <code>{$view}</code> no encontrada.</p>";
            return;
        }

        if ($withLayout) {
            $headerFile = __DIR__ . '/../views/layouts/header.php';
            $footerFile = __DIR__ . '/../views/layouts/footer.php';

            if (file_exists($headerFile)) {
                require $headerFile;
            }

            require $viewFile;

            if (file_exists($footerFile)) {
                require $footerFile;
            }
        } else {
            require $viewFile;
        }
    }

    /**
     * Devuelve una respuesta en formato JSON
     *
     * @param mixed $data
     * @param int $statusCode
     */
    public function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirecciona a una ruta interna o externa
     *
     * @param string $path
     */
    public function redirect(string $path): void {
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            header("Location: " . $path);
        } else {
            header("Location: " . BASE_URL . ltrim($path, '/'));
        }
        exit;
    }
}

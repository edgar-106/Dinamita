<?php
/**
 * Enrutador MVC de INFONATEC
 * Gestiona rutas GET, POST y despacha controladores y métodos
 */

class Router {
    private array $routes = [];

    /**
     * Registra una ruta GET
     */
    public function get(string $path, $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Registra una ruta POST
     */
    public function post(string $path, $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Almacena internamente la ruta
     */
    private function addRoute(string $method, string $path, $handler): void {
        $path = trim($path, '/');
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'handler' => $handler
        ];
    }

    /**
     * Resuelve la URL solicitada y despacha el controlador correspondiente
     */
    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $currentUri = $this->getCurrentUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convertir la ruta en patrón regex para soportar parámetros {id}
            $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([^/]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $currentUri, $matches)) {
                array_shift($matches); // Quitar coincidencia completa
                $this->executeHandler($route['handler'], $matches);
                return;
            }
        }

        // Si no se encuentra la ruta
        $this->notFound($currentUri);
    }

    /**
     * Extrae la URI limpia sin el prefijo del directorio base ni query params
     */
    private function getCurrentUri(): string {
        // Prioridad 1: Parámetro 'url' enviado por .htaccess o URL directa ?url=...
        if (isset($_GET['url'])) {
            return trim($_GET['url'], '/');
        }

        // Prioridad 2: REQUEST_URI quitando la ruta base
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        $basePath = BASE_PATH;
        if ($basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return trim($path, '/');
    }

    /**
     * Ejecuta el controlador o función anónima
     */
    private function executeHandler($handler, array $params = []): void {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_string($handler)) {
            [$controllerName, $action] = explode('@', $handler);

            $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
            if (!file_exists($controllerFile)) {
                http_response_code(500);
                echo "<h1>Error 500</h1><p>Controlador <code>{$controllerName}</code> no encontrado.</p>";
                return;
            }

            require_once $controllerFile;

            if (!class_exists($controllerName)) {
                http_response_code(500);
                echo "<h1>Error 500</h1><p>Clase <code>{$controllerName}</code> no existe.</p>";
                return;
            }

            $controller = new $controllerName();

            if (!method_exists($controller, $action)) {
                http_response_code(500);
                echo "<h1>Error 500</h1><p>Método <code>{$action}</code> no encontrado en <code>{$controllerName}</code>.</p>";
                return;
            }

            call_user_func_array([$controller, $action], $params);
        }
    }

    /**
     * Manejo de error 404
     */
    private function notFound(string $uri): void {
        http_response_code(404);
        echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>404 No Encontrado</title>";
        echo "<style>body{font-family:sans-serif;background:#0d1015;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0;}";
        echo "h1{font-size:72px;color:#c9a86a;margin:0;}p{color:#94a3b8;font-size:18px;}a{color:#c9a86a;text-decoration:none;border:1px solid #c9a86a;padding:10px 20px;border-radius:20px;margin-top:20px;display:inline-block;}</style></head>";
        echo "<body><h1>404</h1><p>La ruta solicitada <code>/" . htmlspecialchars($uri) . "</code> no existe.</p><a href='" . BASE_URL . "'>Volver al Inicio</a></body></html>";
        exit;
    }
}

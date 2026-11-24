<?php
/**
 * Utilidades de seguridad: cabeceras HTTP, protección CSRF y control de intentos.
 * INFONATEC Inmobiliaria
 */

class Security {
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 300;

    /**
     * Envía cabeceras HTTP de endurecimiento básico para todas las respuestas.
     */
    public static function setSecurityHeaders(): void {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=()');
    }

    /**
     * Obtiene o genera el token CSRF de la sesión actual.
     */
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Rota el token CSRF (por ejemplo tras iniciar sesión o registrarse).
     */
    public static function rotateCsrfToken(): string {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifica el token CSRF recibido por cabecera HTTP, POST o cuerpo JSON.
     */
    public static function verifyCsrf(?array $jsonBody = null): bool {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        $provided = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? ($_POST['_csrf'] ?? ($jsonBody['_csrf'] ?? ''));

        if (!is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($sessionToken, trim($provided));
    }

    /**
     * Exige un token CSRF válido o detiene la ejecución con HTTP 403 JSON.
     */
    public static function requireCsrf(?array $jsonBody = null): void {
        if (self::verifyCsrf($jsonBody)) {
            return;
        }

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'csrf_invalid',
            'message' => 'Token de seguridad (CSRF) inválido o expirado. Recarga la página e inténtalo de nuevo.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Determina si la petición actual espera respuesta JSON/AJAX.
     */
    public static function isAjaxRequest(): bool {
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($requestedWith === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (str_contains($accept, 'application/json') || str_contains($accept, 'text/event-stream')) {
            return true;
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        $uri = strtolower((string) ($_GET['url'] ?? ($_SERVER['REQUEST_URI'] ?? '')));
        if (str_contains($uri, '/api/') || str_starts_with($uri, 'api/')) {
            return true;
        }

        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * Comprueba si el usuario actual ha excedido el límite de intentos de login.
     */
    public static function checkLoginRateLimit(): array {
        $record = $_SESSION['login_attempts'] ?? ['count' => 0, 'first_at' => time()];
        if (!is_array($record)) {
            $record = ['count' => 0, 'first_at' => time()];
        }

        $elapsed = time() - (int) ($record['first_at'] ?? time());
        if ($elapsed > self::LOGIN_WINDOW_SECONDS) {
            unset($_SESSION['login_attempts']);
            return ['allowed' => true, 'retry_after' => 0];
        }

        $count = (int) ($record['count'] ?? 0);
        if ($count >= self::LOGIN_MAX_ATTEMPTS) {
            return [
                'allowed'     => false,
                'retry_after' => max(1, self::LOGIN_WINDOW_SECONDS - $elapsed),
            ];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    /**
     * Registra un intento fallido de inicio de sesión.
     */
    public static function recordLoginFailure(): void {
        $record = $_SESSION['login_attempts'] ?? null;
        if (!is_array($record) || (time() - (int) ($record['first_at'] ?? 0)) > self::LOGIN_WINDOW_SECONDS) {
            $_SESSION['login_attempts'] = ['count' => 1, 'first_at' => time()];
            return;
        }
        $record['count'] = ((int) ($record['count'] ?? 0)) + 1;
        $_SESSION['login_attempts'] = $record;
    }

    /**
     * Limpia el contador de intentos fallidos tras autenticación exitosa.
     */
    public static function clearLoginFailures(): void {
        unset($_SESSION['login_attempts']);
    }
}

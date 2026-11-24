<?php
/**
 * Modelo Usuario
 * Gestiona el registro, autenticación y sesión de usuarios
 */

class Usuario extends Model {

    /**
     * Registra un nuevo usuario en la base de datos (sin fallback inseguro).
     */
    public function register(array $userData): array {
        $name     = trim((string) ($userData['name'] ?? ''));
        $last     = trim((string) ($userData['last'] ?? ''));
        $email    = strtolower(trim((string) ($userData['email'] ?? '')));
        $phone    = trim((string) ($userData['phone'] ?? ''));
        $location = trim((string) ($userData['location'] ?? ''));
        $password = (string) ($userData['password'] ?? '');

        if ($name === '' || mb_strlen($name) < 2) {
            return ['success' => false, 'code' => 400, 'message' => 'Ingresa tu nombre completo.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'code' => 400, 'message' => 'Ingresa un correo electrónico válido.'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'code' => 400, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if ($this->db === null) {
            return ['success' => false, 'code' => 503, 'message' => 'Base de datos no disponible. No es posible registrar cuentas en este momento.'];
        }

        try {
            $check = $this->db->prepare("SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                return ['success' => false, 'code' => 409, 'message' => 'El correo electrónico ya está registrado.'];
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $usernameBase = strtolower((string) preg_replace('/[^a-z0-9]/', '', (string) strstr($email, '@', true)));
            $username = substr($usernameBase ?: 'usuario', 0, 52) . '_' . substr(bin2hex(random_bytes(4)), 0, 7);

            $stmt = $this->db->prepare(
                "INSERT INTO usuario (nombre, apellido_paterno, correo, telefono, usuario, contrasena)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $last !== '' ? $last : 'Sin apellido', $email, $phone !== '' ? $phone : null, $username, $hash]);
            $userId = (int) $this->db->lastInsertId();

            // Todo usuario registrado desde el portal recibe rol CLIENTE (nunca ADMIN)
            $rolStmt = $this->db->query("SELECT id_rol FROM rol WHERE nombre = 'CLIENTE' LIMIT 1");
            $clienteRolId = $rolStmt ? $rolStmt->fetchColumn() : false;
            if ($clienteRolId) {
                $urStmt = $this->db->prepare("INSERT IGNORE INTO usuario_rol (id_usuario, id_rol) VALUES (?, ?)");
                $urStmt->execute([$userId, (int) $clienteRolId]);
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $csrf = Security::rotateCsrfToken();

            $user = [
                'id'       => $userId,
                'name'     => $name,
                'last'     => $last,
                'email'    => $email,
                'phone'    => $phone,
                'location' => $location,
                'is_admin' => false,
            ];
            $_SESSION['user'] = $user;

            return ['success' => true, 'code' => 200, 'user' => $user, 'csrf_token' => $csrf];
        } catch (Exception $e) {
            error_log("Error al registrar usuario: " . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'Error al registrar el usuario en la base de datos.'];
        }
    }

    /**
     * Inicia sesión verificando credenciales hash en base de datos.
     */
    public function login(string $email, string $password): array {
        $limit = Security::checkLoginRateLimit();
        if (!$limit['allowed']) {
            return [
                'success' => false,
                'code'    => 429,
                'message' => 'Demasiados intentos fallidos. Espera ' . (int) ceil($limit['retry_after'] / 60) . ' minuto(s) antes de volver a intentarlo.',
            ];
        }

        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return ['success' => false, 'code' => 400, 'message' => 'Ingresa tu correo y contraseña.'];
        }

        if ($this->db === null) {
            return ['success' => false, 'code' => 503, 'message' => 'Base de datos no disponible.'];
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM usuario WHERE correo = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, (string) $user['contrasena'])) {
                Security::clearLoginFailures();

                $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
                $isAdmin = false;
                if ($adminEmail === '' || strtolower((string) $user['correo']) === $adminEmail) {
                    $rolStmt = $this->db->prepare(
                        "SELECT 1 FROM usuario_rol ur INNER JOIN rol r ON r.id_rol = ur.id_rol WHERE ur.id_usuario = ? AND r.nombre = 'ADMIN' LIMIT 1"
                    );
                    $rolStmt->execute([(int) $user['id_usuario']]);
                    $isAdmin = (bool) $rolStmt->fetchColumn();
                }

                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $csrf = Security::rotateCsrfToken();

                $sessionUser = [
                    'id'       => (int) $user['id_usuario'],
                    'name'     => $user['nombre'],
                    'last'     => $user['apellido_paterno'],
                    'email'    => $user['correo'],
                    'phone'    => $user['telefono'],
                    'location' => '',
                    'is_admin' => $isAdmin,
                ];
                $_SESSION['user'] = $sessionUser;

                return ['success' => true, 'code' => 200, 'user' => $sessionUser, 'csrf_token' => $csrf];
            }

            Security::recordLoginFailure();
            return ['success' => false, 'code' => 401, 'message' => 'Correo o contraseña incorrectos.'];
        } catch (Exception $e) {
            error_log("Error al iniciar sesión: " . $e->getMessage());
            return ['success' => false, 'code' => 500, 'message' => 'Error al verificar credenciales.'];
        }
    }

    /**
     * Obtiene el usuario actual logueado (sin exponer datos sensibles).
     */
    public static function current(): ?array {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    /**
     * Cierra la sesión activa e invalida la cookie en el navegador.
     */
    public static function logout(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool) ($params['secure'] ?? false),
                (bool) ($params['httponly'] ?? true)
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

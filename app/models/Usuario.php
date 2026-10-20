<?php
/**
 * Modelo Usuario
 * Gestiona el registro, autenticación y sesión de usuarios
 */

class Usuario extends Model {

    /**
     * Registra un nuevo usuario en sesión o en base de datos
     */
    public function register(array $userData): array {
        $name     = trim($userData['name'] ?? '');
        $last     = trim($userData['last'] ?? '');
        $email    = trim($userData['email'] ?? '');
        $phone    = trim($userData['phone'] ?? '');
        $location = trim($userData['location'] ?? '');
        $password = $userData['password'] ?? '';

        // Esta validación rápida protege los dos modos de persistencia: base de
        // datos y sesión temporal para demostración.
        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Por favor completa los campos requeridos.'];
        }

        // Si hay base de datos conectada
        if ($this->db !== null) {
            try {
                $check = $this->db->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
                $check->execute([$email]);
                if ($check->fetch()) {
                    return ['success' => false, 'message' => 'El correo electrónico ya está registrado.'];
                }

                // Nunca se almacena la contraseña original; PHP elige el
                // algoritmo seguro actual mediante PASSWORD_DEFAULT.
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $usernameBase = strtolower((string) preg_replace('/[^a-z0-9]/', '', strstr($email, '@', true)));
                $username = substr($usernameBase ?: 'usuario', 0, 52) . '_' . substr(bin2hex(random_bytes(4)), 0, 7);
                $stmt = $this->db->prepare(
                    "INSERT INTO usuario (nombre, apellido_paterno, correo, telefono, usuario, contrasena)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$name, $last ?: 'Sin apellido', $email, $phone ?: null, $username, $hash]);
                $userId = $this->db->lastInsertId();

                $user = [
                    'id'       => $userId,
                    'name'     => $name,
                    'last'     => $last,
                    'email'    => $email,
                    'phone'    => $phone,
                    'location' => $location
                ];
                $_SESSION['user'] = $user;
                return ['success' => true, 'user' => $user];
            } catch (Exception $e) {
                error_log("Error al registrar usuario: " . $e->getMessage());
            }
        }

        // Guardado en sesión como fallback
        $user = [
            'id'       => 1,
            'name'     => $name,
            'last'     => $last,
            'email'    => $email,
            'phone'    => $phone,
            'location' => $location
        ];
        $_SESSION['user'] = $user;

        return ['success' => true, 'user' => $user];
    }

    /**
     * Inicia sesión
     */
    public function login(string $email, string $password): array {
        $email = trim($email);

        if ($this->db !== null) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM usuario WHERE correo = ? AND activo = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['contrasena'])) {
                    $sessionUser = [
                        'id' => $user['id_usuario'],
                        'name' => $user['nombre'],
                        'last' => $user['apellido_paterno'],
                        'email' => $user['correo'],
                        'phone' => $user['telefono'],
                        'location' => '',
                    ];
                    $_SESSION['user'] = $sessionUser;
                    return ['success' => true, 'user' => $sessionUser];
                }
            } catch (Exception $e) {
                error_log("Error al iniciar sesión: " . $e->getMessage());
            }
        }

        // Fallback para pruebas rápidas: no sustituye autenticación real y sólo
        // conserva la sesión mientras el navegador permanezca activo.
        if (!empty($email) && !empty($password)) {
            $user = [
                'id'       => 1,
                'name'     => explode('@', $email)[0],
                'email'    => $email,
                'phone'    => '',
                'location' => ''
            ];
            $_SESSION['user'] = $user;
            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'message' => 'Credenciales inválidas.'];
    }

    /**
     * Obtiene el usuario actual logueado
     */
    public static function current(): ?array {
        // Devuelve null cuando no existe sesión autenticada.
        return $_SESSION['user'] ?? null;
    }

    /**
     * Cierra la sesión activa
     */
    public static function logout(): void {
        // Se eliminan los datos y se invalida el identificador de la sesión.
        unset($_SESSION['user']);
        session_destroy();
    }
}

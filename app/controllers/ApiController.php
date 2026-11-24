<?php
/**
 * Punto de acceso JSON de la aplicación.
 * Hereda las acciones de IA y agrega catálogo, citas, contacto y favoritos.
 */

class ApiController extends AIController {

    /**
     * Lee payload JSON o POST estándar de formulario.
     */
    private function parseInput(): array {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return !empty($_POST) && is_array($_POST) ? $_POST : [];
    }

    public function properties(): void {
        $this->json((new Propiedad())->getAll());
    }

    public function property(string $id): void {
        $property = (new Propiedad())->getById((int) $id);
        $this->json($property ?: ['error' => 'Propiedad no encontrada'], $property ? 200 : 404);
    }

    /**
     * Registra una cita real en la tabla `cita`.
     */
    public function createAppointment(): void {
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Base de datos no disponible para agendar citas.'], 503);
        }

        $propertyId = (int) ($input['id_propiedad'] ?? 0);
        $fecha = trim((string) ($input['fecha'] ?? ''));
        $hora = trim((string) ($input['hora'] ?? ''));
        $medioRaw = strtoupper(trim((string) ($input['medio_contacto'] ?? 'WHATSAPP')));
        $telefono = trim((string) ($input['telefono'] ?? ''));
        $observaciones = trim((string) ($input['observaciones'] ?? ''));

        if ($propertyId <= 0) {
            $this->json(['success' => false, 'message' => 'Selecciona una propiedad válida para agendar tu cita.'], 400);
        }

        $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $fecha) {
            $this->json(['success' => false, 'message' => 'Ingresa una fecha válida (AAAA-MM-DD).'], 400);
        }
        if ($fecha < date('Y-m-d')) {
            $this->json(['success' => false, 'message' => 'La fecha de la cita no puede ser anterior a hoy.'], 400);
        }

        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $hora)) {
            $this->json(['success' => false, 'message' => 'Ingresa una hora válida en formato 24h (HH:MM).'], 400);
        }
        if (strlen($hora) === 5) {
            $hora .= ':00';
        }

        $digits = preg_replace('/\D+/', '', $telefono);
        if (strlen((string) $digits) < 8) {
            $this->json(['success' => false, 'message' => 'Ingresa un teléfono de contacto válido (al menos 8 dígitos).'], 400);
        }

        $medioMap = [
            'WHATSAPP' => 'WHATSAPP',
            'LLAMADA' => 'TELEFONO',
            'TELEFONO' => 'TELEFONO',
            'CORREO' => 'CORREO',
            'MENSAJE DENTRO DEL SISTEMA' => 'SISTEMA',
            'SISTEMA' => 'SISTEMA',
        ];
        $medio = $medioMap[$medioRaw] ?? 'WHATSAPP';

        $currentUser = Usuario::current();
        $userId = (int) ($currentUser['id'] ?? 0);

        if ($userId <= 0) {
            $guestName = trim((string) ($input['nombre'] ?? ''));
            $guestEmail = strtolower(trim((string) ($input['correo'] ?? '')));
            if (mb_strlen($guestName) < 2 || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                $this->json([
                    'success' => false,
                    'message' => 'Para confirmar la cita ingresa tu nombre completo y correo electrónico.',
                ], 400);
            }
            $userId = $this->findOrCreateClientUser($db, $guestName, $guestEmail, $telefono);
            if ($userId <= 0) {
                $this->json(['success' => false, 'message' => 'No fue posible registrar tus datos de contacto para la cita.'], 500);
            }
        }

        try {
            $propCheck = $db->prepare('SELECT titulo FROM propiedad WHERE id_propiedad = ? LIMIT 1');
            $propCheck->execute([$propertyId]);
            $propTitle = $propCheck->fetchColumn();
            if (!$propTitle) {
                $this->json(['success' => false, 'message' => 'La propiedad seleccionada no existe en el catálogo.'], 404);
            }

            $estadoId = $db->query("SELECT id_estado_cita FROM estado_cita WHERE nombre = 'PENDIENTE' LIMIT 1")->fetchColumn();
            if (!$estadoId) {
                $this->json(['success' => false, 'message' => 'Catálogo de estados de cita no inicializado.'], 500);
            }

            $motivo = 'Visita / orientación sobre ' . mb_substr((string) $propTitle, 0, 180);
            $obsFull = trim('Tel: ' . $telefono . ($observaciones !== '' ? ' · ' . $observaciones : ''));

            $ins = $db->prepare(
                'INSERT INTO cita (id_usuario, id_propiedad, id_asesor, id_estado_cita, fecha, hora, medio_contacto, motivo, observaciones)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$userId, $propertyId, (int) $estadoId, $fecha, $hora, $medio, $motivo, $obsFull]);

            $this->json([
                'success' => true,
                'id_cita' => (int) $db->lastInsertId(),
                'message' => '¡Cita registrada para ' . $propTitle . ' el ' . $fecha . ' a las ' . substr($hora, 0, 5) . '!',
            ]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'Ese horario ya está reservado para esta propiedad. Por favor elige otra fecha u hora.',
                ], 409);
            }
            error_log('Error creando cita: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'No fue posible registrar la cita. Inténtalo de nuevo.'], 500);
        }
    }

    /**
     * Registra una solicitud de interés, asesoría o cotización en `solicitud_contacto`.
     */
    public function createContact(): void {
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Base de datos no disponible para registrar la solicitud.'], 503);
        }

        $tipo = strtoupper(trim((string) ($input['tipo'] ?? 'INTERES')));
        if (!in_array($tipo, ['INTERES', 'ASESORIA', 'TOUR_SOLICITUD'], true)) {
            $tipo = 'INTERES';
        }

        $currentUser = Usuario::current();
        $userId = !empty($currentUser['id']) ? (int) $currentUser['id'] : null;

        $nombre = trim((string) ($input['nombre'] ?? ($currentUser['name'] ?? '')));
        $telefono = trim((string) ($input['telefono'] ?? ($currentUser['phone'] ?? '')));
        $correo = strtolower(trim((string) ($input['correo'] ?? ($currentUser['email'] ?? ''))));
        $horario = trim((string) ($input['horario_preferido'] ?? ''));
        $mensaje = trim((string) ($input['mensaje'] ?? ''));
        $propertyId = !empty($input['id_propiedad']) ? (int) $input['id_propiedad'] : null;

        if (mb_strlen($nombre) < 2) {
            $this->json(['success' => false, 'message' => 'Por favor ingresa tu nombre completo.'], 400);
        }
        $digits = preg_replace('/\D+/', '', $telefono);
        if (strlen((string) $digits) < 8) {
            $this->json(['success' => false, 'message' => 'Ingresa un número telefónico válido (al menos 8 dígitos).'], 400);
        }
        if ($tipo !== 'ASESORIA' && ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL))) {
            $this->json(['success' => false, 'message' => 'Ingresa un correo electrónico válido para responderte.'], 400);
        }
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'El formato del correo electrónico no es válido.'], 400);
        }

        try {
            if ($propertyId !== null && $propertyId > 0) {
                $chk = $db->prepare('SELECT 1 FROM propiedad WHERE id_propiedad = ? LIMIT 1');
                $chk->execute([$propertyId]);
                if (!$chk->fetchColumn()) {
                    $propertyId = null;
                }
            } else {
                $propertyId = null;
            }

            $ins = $db->prepare(
                'INSERT INTO solicitud_contacto (id_propiedad, id_usuario, tipo, nombre, correo, telefono, horario_preferido, mensaje, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $propertyId,
                $userId,
                $tipo,
                mb_substr($nombre, 0, 140),
                $correo !== '' ? mb_substr($correo, 0, 150) : null,
                mb_substr($telefono, 0, 30),
                $horario !== '' ? mb_substr($horario, 0, 60) : null,
                $mensaje !== '' ? $mensaje : null,
                'NUEVA',
            ]);

            $this->json([
                'success'      => true,
                'id_solicitud' => (int) $db->lastInsertId(),
                'message'      => 'Solicitud enviada correctamente. Un asesor de INFONATEC se pondrá en contacto contigo.',
            ]);
        } catch (Exception $e) {
            error_log('Error registrando solicitud_contacto: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'No fue posible enviar tu solicitud. Intenta nuevamente.'], 500);
        }
    }

    /**
     * Lista los IDs de propiedades favoritas del usuario autenticado.
     */
    public function favorites(): void {
        $user = Usuario::current();
        if (empty($user['id'])) {
            $this->json(['authenticated' => false, 'favorites' => []]);
        }

        $favs = (new Propiedad())->getUserFavoriteIds((int) $user['id']);
        $this->json(['authenticated' => true, 'favorites' => $favs]);
    }

    /**
     * Guarda o quita una propiedad de los favoritos del usuario en BD.
     */
    public function toggleFavorite(): void {
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $user = Usuario::current();
        if (empty($user['id'])) {
            $this->json(['success' => false, 'authenticated' => false, 'message' => 'Sesión no iniciada.'], 401);
        }

        $propertyId = (int) ($input['id_propiedad'] ?? 0);
        if ($propertyId <= 0) {
            $this->json(['success' => false, 'message' => 'Propiedad inválida.'], 400);
        }

        $result = (new Propiedad())->toggleFavorite((int) $user['id'], $propertyId);
        $this->json($result, !empty($result['success']) ? 200 : 400);
    }

    /**
     * Sincroniza favoritos del navegador hacia la cuenta autenticada.
     */
    public function syncFavorites(): void {
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $user = Usuario::current();
        if (empty($user['id'])) {
            $this->json(['success' => false, 'authenticated' => false], 401);
        }

        $ids = is_array($input['favorites'] ?? null) ? $input['favorites'] : [];
        $merged = (new Propiedad())->syncFavorites((int) $user['id'], $ids);
        $this->json(['success' => true, 'favorites' => $merged]);
    }

    /**
     * Localiza o crea un cliente por correo cuando un visitante agenda cita sin login previo.
     */
    private function findOrCreateClientUser(PDO $db, string $fullName, string $email, string $phone): int {
        try {
            $stmt = $db->prepare('SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1');
            $stmt->execute([$email]);
            $existingId = $stmt->fetchColumn();
            if ($existingId) {
                return (int) $existingId;
            }

            $parts = preg_split('/\s+/', trim($fullName), 2);
            $usernameBase = strtolower((string) preg_replace('/[^a-z0-9]/', '', (string) strstr($email, '@', true)));
            $username = substr($usernameBase ?: 'visitante', 0, 52) . '_' . substr(bin2hex(random_bytes(4)), 0, 7);

            $ins = $db->prepare(
                'INSERT INTO usuario (nombre, apellido_paterno, correo, telefono, usuario, contrasena) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $parts[0] ?: 'Visitante',
                $parts[1] ?? 'INFONATEC',
                $email,
                $phone !== '' ? $phone : null,
                $username,
                password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            ]);
            $newId = (int) $db->lastInsertId();

            $rolId = $db->query("SELECT id_rol FROM rol WHERE nombre = 'CLIENTE' LIMIT 1")->fetchColumn();
            if ($rolId) {
                $ur = $db->prepare('INSERT IGNORE INTO usuario_rol (id_usuario, id_rol) VALUES (?, ?)');
                $ur->execute([$newId, (int) $rolId]);
            }
            return $newId;
        } catch (Exception $e) {
            error_log('Error creando cliente visitante para cita: ' . $e->getMessage());
            return 0;
        }
    }
}

<?php
/**
 * Controlador de contacto y citas (compatibilidad con rutas públicas /contacto y /citas).
 */

class ContactController extends Controller {
    private function parseInput(): array {
        if (!empty($_POST)) {
            return $_POST;
        }
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    public function interest(): void {
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $name = trim((string) ($input['name'] ?? ($input['nombre'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? ($input['telefono'] ?? '')));
        $email = trim((string) ($input['email'] ?? ($input['correo'] ?? '')));
        $message = trim((string) ($input['message'] ?? ($input['mensaje'] ?? '')));
        $schedule = trim((string) ($input['schedule'] ?? ($input['horario_preferido'] ?? '')));
        $propertyId = (int) ($input['property_id'] ?? ($input['id_propiedad'] ?? 0));

        if ($name === '' || $phone === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $this->json(['success' => false, 'message' => 'Revisa el nombre, teléfono y correo.'], 400);
        }

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 503);
        }

        $currentUser = Usuario::current();
        $userId = !empty($currentUser['id']) ? (int) $currentUser['id'] : null;

        try {
            $stmt = $db->prepare(
                'INSERT INTO solicitud_contacto (id_propiedad, id_usuario, tipo, nombre, correo, telefono, horario_preferido, mensaje, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $propertyId > 0 ? $propertyId : null,
                $userId,
                'INTERES',
                mb_substr($name, 0, 140),
                $email !== '' ? mb_substr($email, 0, 150) : null,
                mb_substr($phone, 0, 30),
                $schedule !== '' ? mb_substr($schedule, 0, 60) : null,
                $message !== '' ? $message : null,
                'NUEVA',
            ]);
            $this->json(['success' => true, 'message' => 'Solicitud registrada correctamente.']);
        } catch (PDOException $e) {
            $this->json(['success' => false, 'message' => 'No fue posible registrar la solicitud.'], 400);
        }
    }

    public function appointment(): void {
        $input = $this->parseInput();
        Security::requireCsrf($input);

        $user = Usuario::current();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Inicia sesión para solicitar una cita.'], 401);
        }

        $date = trim((string) ($input['date'] ?? ($input['fecha'] ?? '')));
        $time = trim((string) ($input['time'] ?? ($input['hora'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? ($input['telefono'] ?? '')));
        $propertyId = (int) ($input['property_id'] ?? ($input['id_propiedad'] ?? 0));
        $contact = trim((string) ($input['contact'] ?? ($input['medio_contacto'] ?? 'TELÉFONO')));

        $when = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $time");
        if (!$when || $when->format('Y-m-d H:i') !== "$date $time" || $when <= new DateTimeImmutable() || $phone === '' || $propertyId <= 0) {
            $this->json(['success' => false, 'message' => 'Selecciona una propiedad, una fecha futura y tu teléfono.'], 400);
        }

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 503);
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                "INSERT INTO cita (id_usuario, id_propiedad, id_estado_cita, fecha, hora, medio_contacto)
                 SELECT ?, ?, id_estado_cita, ?, ?, ? FROM estado_cita WHERE nombre = 'PENDIENTE'"
            );
            $stmt->execute([$user['id'], $propertyId, $date, $time, $contact]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Falta el estado inicial.');
            }
            $db->prepare('UPDATE usuario SET telefono = ? WHERE id_usuario = ?')->execute([$phone, $user['id']]);
            $db->commit();
            $this->json(['success' => true, 'message' => 'Cita registrada y pendiente de confirmación.']);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->json(['success' => false, 'message' => 'La cita no pudo registrarse. Verifica que el horario esté disponible.'], 409);
        }
    }
}

<?php

class ContactController extends Controller {
    public function interest(): void {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $propertyId = (int) ($_POST['property_id'] ?? 0);
        if ($name === '' || $phone === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $this->json(['success' => false, 'message' => 'Revisa el nombre, teléfono y correo.'], 400);
        }
        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 503);
        }
        try {
            $stmt = $db->prepare('INSERT INTO solicitud_contacto (id_propiedad, nombre, telefono, correo, mensaje, horario) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$propertyId ?: null, $name, $phone, $email ?: null, trim($_POST['message'] ?? ''), trim($_POST['schedule'] ?? '')]);
            $this->json(['success' => true, 'message' => 'Solicitud registrada correctamente.']);
        } catch (PDOException $e) {
            $this->json(['success' => false, 'message' => 'No fue posible registrar la solicitud.'], 400);
        }
    }

    public function appointment(): void {
        $user = Usuario::current();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Inicia sesión para solicitar una cita.'], 401);
        }
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $propertyId = (int) ($_POST['property_id'] ?? 0);
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
            $stmt = $db->prepare("INSERT INTO cita (id_usuario, id_propiedad, id_estado_cita, fecha, hora, medio_contacto) SELECT ?, ?, id_estado_cita, ?, ?, ? FROM estado_cita WHERE nombre = 'PENDIENTE'");
            $stmt->execute([$user['id'], $propertyId, $date, $time, trim($_POST['contact'] ?? 'TELÉFONO')]);
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

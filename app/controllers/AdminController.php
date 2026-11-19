<?php
/**
 * Controlador del Panel de Administración
 * Ruta principal: /admin
 *
 * Fase 1 - Módulo: Gestión de Reservaciones
 * El administrador puede visualizar y controlar el estado de las
 * reservas (citas) registradas sobre las propiedades del catálogo.
 */

class AdminController extends Controller {

    /**
     * Valida que la petición provenga de un usuario con rol ADMIN.
     * Si no lo es, redirige a la portada pública.
     */
    private function requireAdmin(): array {
        $user = Usuario::current();
        if (empty($user) || empty($user['id'])) {
            $this->redirect('vender');
            return [];
        }

        $db = Database::getConnection();
        if ($db !== null) {
            try {
                $stmt = $db->prepare(
                    "SELECT r.nombre AS rol FROM usuario_rol ur INNER JOIN rol r ON r.id_rol = ur.id_rol WHERE ur.id_usuario = ? AND r.nombre = 'ADMIN' LIMIT 1"
                );
                $stmt->execute([(int) $user['id']]);
                if (!$stmt->fetch()) {
                    $this->redirect('vender');
                    return [];
                }
            } catch (Exception $e) {
                error_log("Error validando acceso de administrador: " . $e->getMessage());
                $this->redirect('vender');
                return [];
            }
        }

        return $user;
    }

    /**
     * Panel principal: indicadores y accesos rápidos.
     */
    public function index(): void {
        $admin = $this->requireAdmin();
        $db = Database::getConnection();

        $kpis = [
            'reservas_totales' => 0,
            'reservas_pendientes' => 0,
            'reservas_confirmadas' => 0,
            'pagos_pendientes' => 0,
            'propiedades_disponibles' => 0,
        ];

        $reservasRecientes = [];

        if ($db !== null) {
            try {
                $kpis['reservas_totales'] = (int) $db->query("SELECT COUNT(*) FROM cita")->fetchColumn();
                $kpis['reservas_pendientes'] = (int) $db->query(
                    "SELECT COUNT(*) FROM cita c INNER JOIN estado_cita e ON e.id_estado_cita = c.id_estado_cita WHERE e.nombre = 'PENDIENTE'"
                )->fetchColumn();
                $kpis['reservas_confirmadas'] = (int) $db->query(
                    "SELECT COUNT(*) FROM cita c INNER JOIN estado_cita e ON e.id_estado_cita = c.id_estado_cita WHERE e.nombre = 'CONFIRMADA'"
                )->fetchColumn();
                $kpis['pagos_pendientes'] = (int) $db->query("SELECT COUNT(*) FROM transaccion WHERE estado = 'PENDIENTE'")->fetchColumn();
                $kpis['propiedades_disponibles'] = (int) $db->query(
                    "SELECT COUNT(*) FROM propiedad p INNER JOIN estado_propiedad e ON e.id_estado = p.id_estado WHERE e.nombre = 'DISPONIBLE'"
                )->fetchColumn();

                $reservasRecientes = $db->query(
                    "SELECT c.id_cita, c.fecha, c.hora, p.titulo AS propiedad, u.nombre AS cliente_nombre, u.apellido_paterno AS cliente_apellido, u.correo AS cliente_correo, e.nombre AS estado
                     FROM cita c
                     INNER JOIN propiedad p ON p.id_propiedad = c.id_propiedad
                     INNER JOIN usuario u ON u.id_usuario = c.id_usuario
                     INNER JOIN estado_cita e ON e.id_estado_cita = c.id_estado_cita
                     ORDER BY c.fecha DESC, c.hora DESC
                     LIMIT 8"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error consultando panel de administración: " . $e->getMessage());
            }
        }

        $this->renderView('admin/index', [
            'pageTitle' => 'Panel de Administración | INFONATEC',
            'activePage' => 'admin',
            'showSplash' => false,
            'adminUser' => $admin,
            'kpis' => $kpis,
            'reservas_recientes' => $reservasRecientes,
            'estados_citas' => $this->getEstadosCitas(),
        ]);
    }

    /**
     * Listado de todas las reservas (citas) con el detalle de la propiedad,
     * el cliente y el asesor asignado.
     */
    public function reservas(): void {
        $admin = $this->requireAdmin();
        $db = Database::getConnection();

        $reservas = [];
        if ($db !== null) {
            try {
                $reservas = $db->query(
                    "SELECT c.id_cita, c.fecha, c.hora, c.motivo, c.observaciones, p.titulo AS propiedad, p.operacion, p.ubicacion, p.precio, u.nombre AS cliente_nombre, u.apellido_paterno AS cliente_apellido, u.correo AS cliente_correo, u.telefono AS cliente_telefono, a.especialidad AS asesor, e.nombre AS estado
                     FROM cita c
                     INNER JOIN propiedad p ON p.id_propiedad = c.id_propiedad
                     INNER JOIN usuario u ON u.id_usuario = c.id_usuario
                     LEFT JOIN asesor_inmobiliario a ON a.id_asesor = c.id_asesor
                     INNER JOIN estado_cita e ON e.id_estado_cita = c.id_estado_cita
                     ORDER BY c.fecha DESC, c.hora DESC"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error consultando reservas de administración: " . $e->getMessage());
            }
        }

        $this->renderView('admin/reservas', [
            'pageTitle' => 'Gestión de Reservas | INFONATEC',
            'activePage' => 'admin',
            'showSplash' => false,
            'adminUser' => $admin,
            'reservas' => $reservas,
            'estados_citas' => $this->getEstadosCitas(),
        ]);
    }

    /**
     * Actualiza el estado de una reserva desde el panel de administración.
     */
    public function cambiarEstado(string $id): void {
        $admin = $this->requireAdmin();
        $id = (int) $id;
        $estado = trim((string) ($_POST['estado'] ?? ''));

        if ($id <= 0 || $estado === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos.'], 400);
        }

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 500);
        }

        $stmt = $db->prepare("SELECT id_estado_cita FROM estado_cita WHERE nombre = ? LIMIT 1");
        $stmt->execute([$estado]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->json(['success' => false, 'message' => 'Estado no válido.'], 400);
        }

        $update = $db->prepare("UPDATE cita SET id_estado_cita = ? WHERE id_cita = ?");
        $update->execute([$row['id_estado_cita'], $id]);

        if ($update->rowCount() === 0) {
            $this->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        $this->json(['success' => true, 'id_cita' => $id, 'estado' => $estado]);
    }

    /**
     * Estados de cita disponibles para el selector del panel.
     */
    private function getEstadosCitas(): array {
        $db = Database::getConnection();
        $estados = [];
        if ($db !== null) {
            try {
                $estados = $db->query("SELECT id_estado_cita, nombre FROM estado_cita ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error consultando estados de cita: " . $e->getMessage());
            }
        }
        return $estados;
    }

    /**
     * Listado de transacciones/pagos registrados para validación administrativa.
     */
    public function pagos(): void {
        $admin = $this->requireAdmin();
        $db = Database::getConnection();

        $pagos = [];
        if ($db !== null) {
            try {
                $pagos = $db->query(
                    "SELECT t.id_transaccion, t.monto, t.tipo, t.estado AS estado_transaccion, t.fecha,
                            p.titulo AS propiedad, p.ubicacion AS ubicacion, p.precio AS precio_propiedad,
                            u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido, u.correo AS comprador_correo,
                            pay.metodo_pago, pay.referencia, pay.monto AS monto_pago, pay.estado AS estado_pago
                     FROM transaccion t
                     INNER JOIN propiedad p ON p.id_propiedad = t.id_propiedad
                     INNER JOIN usuario u ON u.id_usuario = t.id_comprador
                     INNER JOIN pago pay ON pay.id_transaccion = t.id_transaccion
                     ORDER BY t.fecha DESC"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error consultando pagos de administracion: " . $e->getMessage());
            }
        }

        $this->renderView('admin/pagos', [
            'pageTitle' => 'Validacion de Pagos | INFONATEC',
            'activePage' => 'admin',
            'showSplash' => false,
            'adminUser' => $admin,
            'pagos' => $pagos,
            'pagos_pendientes' => $this->contarPagosPendientes(),
        ]);
    }

    /**
     * Confirma el pago de una transaccion y libera la propiedad manualmente.
     */
    public function confirmarPago(string $id): void {
        $admin = $this->requireAdmin();
        $transId = (int) $id;
        $db = Database::getConnection();

        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexion con la base de datos.'], 500);
        }

        $trans = $db->prepare("SELECT id_transaccion, id_propiedad, monto, tipo, estado FROM transaccion WHERE id_transaccion = ? LIMIT 1");
        $trans->execute([$transId]);
        $transaccion = $trans->fetch();

        if (!$transaccion) {
            $this->json(['success' => false, 'message' => 'Transaccion no encontrada.'], 404);
        }

        $updTrans = $db->prepare("UPDATE transaccion SET estado = 'CONFIRMADO' WHERE id_transaccion = ?");
        $updTrans->execute([$transId]);

        $updPago = $db->prepare("UPDATE pago SET estado = 'CONFIRMADO' WHERE id_transaccion = ?");
        $updPago->execute([$transId]);

        $prop = $db->prepare("SELECT id_propiedad, id_estado FROM propiedad WHERE id_propiedad = ? LIMIT 1");
        $prop->execute([$transaccion['id_propiedad']]);
        $propiedad = $prop->fetch();

        $liberada = false;
        if ($propiedad) {
            $updEstado = $db->prepare("UPDATE estado_propiedad SET nombre = 'DISPONIBLE' WHERE id_estado = ?");
            $updEstado->execute([$propiedad['id_estado']]);
            $liberada = true;
        }

        $this->json([
            'success' => true,
            'id_transaccion' => $transId,
            'monto' => $transaccion['monto'],
            'tipo' => $transaccion['tipo'],
            'pago_confirmado' => true,
            'propiedad_liberada' => $liberada,
        ]);
    }

    /**
     * Cuenta los pagos pendientes de validacion.
     */
    private function contarPagosPendientes(): int {
        $db = Database::getConnection();
        if ($db === null) {
            return 0;
        }
        try {
            $st = $db->query("SELECT COUNT(*) FROM transaccion WHERE estado = 'PENDIENTE'");
            return (int) $st->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Panel de Eventos / Atracciones: gestiona los recorridos virtuales 3D
     * (atracciones turisticas inmersivas) y consulta las visitas programadas.
     */
    public function eventos(): void {
        $admin = $this->requireAdmin();
        $db = Database::getConnection();

        $kpis = [
            'tours_activos' => 0,
            'propiedades_con_tour' => 0,
            'citas_programadas' => 0,
        ];

        $tours = [];

        if ($db !== null) {
            try {
                $kpis['tours_activos'] = (int) $db->query("SELECT COUNT(*) FROM paseo_virtual")->fetchColumn();
                $kpis['propiedades_con_tour'] = (int) $db->query("SELECT COUNT(DISTINCT id_propiedad) FROM paseo_virtual")->fetchColumn();
                $kpis['citas_programadas'] = (int) $db->query("SELECT COUNT(*) FROM cita")->fetchColumn();

                $tours = $db->query(
                    "SELECT pv.id_paseo, pv.id_propiedad, pv.url_3d, pv.descripcion, pv.fecha_registro,
                            p.titulo AS propiedad, p.ubicacion AS ubicacion, p.operacion
                     FROM paseo_virtual pv
                     INNER JOIN propiedad p ON p.id_propiedad = pv.id_propiedad
                     ORDER BY pv.fecha_registro DESC"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error consultando eventos/tours: " . $e->getMessage());
            }
        }

        $propiedades = (new Propiedad())->getAll();

        $this->renderView('admin/eventos', [
            'pageTitle' => 'Eventos y Recorridos | INFONATEC',
            'activePage' => 'admin',
            'showSplash' => false,
            'adminUser' => $admin,
            'kpis' => $kpis,
            'tours' => $tours,
            'propiedades' => $propiedades,
            'pagos_pendientes' => $this->contarPagosPendientes(),
        ]);
    }

    /**
     * Registra o actualiza el recorrido virtual 3D (atraccion) de una propiedad.
     */
    public function registrarTour(): void {
        $admin = $this->requireAdmin();
        $idProp = (int) ($_POST['id_propiedad'] ?? 0);
        $url = trim((string) ($_POST['url_3d'] ?? ''));
        $desc = trim((string) ($_POST['descripcion'] ?? ''));

        if ($idProp <= 0 || $url === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos: la propiedad y la URL del tour son requeridos.'], 400);
        }

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 500);
        }

        try {
            $upd = $db->prepare("UPDATE paseo_virtual SET url_3d = ?, descripcion = ? WHERE id_propiedad = ?");
            $upd->execute([$url, $desc, $idProp]);
            $existed = $upd->rowCount() > 0;

            if (!$existed) {
                $ins = $db->prepare("INSERT INTO paseo_virtual (id_propiedad, url_3d, descripcion) VALUES (?, ?, ?)");
                $ins->execute([$idProp, $url, $desc]);
            }

            $this->json(['success' => true, 'id_propiedad' => $idProp, 'url_3d' => $url, 'tour_registrado' => true]);
        } catch (Exception $e) {
            error_log("Error registrando tour virtual: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error al registrar el tour: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina el recorrido virtual de una propiedad.
     */
    public function eliminarTour(string $idPaseo): void {
        $admin = $this->requireAdmin();
        $paseoId = (int) $idPaseo;
        $db = Database::getConnection();

        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 500);
        }

        try {
            $del = $db->prepare("DELETE FROM paseo_virtual WHERE id_paseo = ?");
            $del->execute([$paseoId]);

            if ($del->rowCount() === 0) {
                $this->json(['success' => false, 'message' => 'Tour no encontrado.'], 404);
            }

            $this->json(['success' => true, 'id_paseo' => $paseoId, 'eliminado' => true]);
        } catch (Exception $e) {
            error_log("Error eliminando tour virtual: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error al eliminar el tour: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Panel de Logística / Transporte: vehículos y asignaciones de traslado.
     */
    public function logistica(): void {
        $admin = $this->requireAdmin();
        $db = Database::getConnection();

        $kpis = [
            'vehiculos_totales' => 0,
            'vehiculos_disponibles' => 0,
            'vehiculos_en_uso' => 0,
            'asignaciones_activas' => 0,
        ];

        $vehiculos = [];
        $asignaciones = [];

        if ($db !== null) {
            try {
                $kpis['vehiculos_totales'] = (int) $db->query("SELECT COUNT(*) FROM vehiculo")->fetchColumn();
                $kpis['vehiculos_disponibles'] = (int) $db->query("SELECT COUNT(*) FROM vehiculo WHERE estado = 'DISPONIBLE'")->fetchColumn();
                $kpis['vehiculos_en_uso'] = (int) $db->query("SELECT COUNT(*) FROM vehiculo WHERE estado = 'EN_USO'")->fetchColumn();
                $kpis['asignaciones_activas'] = (int) $db->query("SELECT COUNT(*) FROM asignacion_transporte WHERE estado = 'PROGRAMADA'")->fetchColumn();

                $vehiculos = $db->query("SELECT id_vehiculo, placa, tipo, marca, modelo, capacidad, estado, observaciones, fecha_registro FROM vehiculo ORDER BY fecha_registro DESC")->fetchAll(PDO::FETCH_ASSOC);

                $asignaciones = $db->query(
                    "SELECT a.id_asignacion, a.fecha_salida, a.fecha_llegada, a.estado, v.placa AS vehiculo_placa, v.tipo AS vehiculo_tipo, p.titulo AS propiedad
                     FROM asignacion_transporte a
                     INNER JOIN vehiculo v ON v.id_vehiculo = a.id_vehiculo
                     INNER JOIN propiedad p ON p.id_propiedad = a.id_propiedad
                     ORDER BY a.fecha_salida DESC"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error consultando logistica/transporte: " . $e->getMessage());
            }
        }

        $this->renderView('admin/logistica', [
            'pageTitle' => 'Logística de Transporte | INFONATEC',
            'activePage' => 'admin',
            'showSplash' => false,
            'adminUser' => $admin,
            'kpis' => $kpis,
            'vehiculos' => $vehiculos,
            'asignaciones' => $asignaciones,
            'propiedades' => (new Propiedad())->getAll(),
        ]);
    }

    /**
     * Registra un nuevo vehiculo en el parque de transporte.
     */
    public function crearVehiculo(): void {
        $admin = $this->requireAdmin();
        $placa = strtoupper(trim((string) ($_POST['placa'] ?? '')));
        $tipo = strtoupper(trim((string) ($_POST['tipo'] ?? '')));
        $marca = trim((string) ($_POST['marca'] ?? ''));
        $modelo = trim((string) ($_POST['modelo'] ?? ''));
        $capacidad = (int) ($_POST['capacidad'] ?? 0);
        $estado = strtoupper(trim((string) ($_POST['estado'] ?? 'DISPONIBLE')));
        $obs = trim((string) ($_POST['observaciones'] ?? ''));

        if ($placa === '' || $tipo === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos: la placa y el tipo son requeridos.'], 400);
        }
        if (!in_array($estado, ['DISPONIBLE', 'EN_USO', 'MANTENIMIENTO'], true)) {
            $estado = 'DISPONIBLE';
        }

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 500);
        }

        try {
            $ins = $db->prepare("INSERT INTO vehiculo (placa, tipo, marca, modelo, capacidad, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$placa, $tipo, $marca, $modelo, $capacidad, $estado, $obs]);

            $this->json(['success' => true, 'id_vehiculo' => (int) $db->lastInsertId(), 'placa' => $placa, 'estado' => $estado]);
        } catch (Exception $e) {
            error_log("Error creando vehiculo: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error al crear el vehiculo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cambia el estado operativo de un vehiculo.
     */
    public function cambiarEstadoVehiculo(string $id): void {
        $admin = $this->requireAdmin();
        $vehId = (int) $id;
        $estado = strtoupper(trim((string) ($_POST['estado'] ?? '')));

        if ($vehId <= 0 || !in_array($estado, ['DISPONIBLE', 'EN_USO', 'MANTENIMIENTO'], true)) {
            $this->json(['success' => false, 'message' => 'Datos inválidos. Estado no permitido.'], 400);
        }

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 500);
        }

        try {
            $upd = $db->prepare("UPDATE vehiculo SET estado = ? WHERE id_vehiculo = ?");
            $upd->execute([$estado, $vehId]);

            if ($upd->rowCount() === 0) {
                $this->json(['success' => false, 'message' => 'Vehículo no encontrado.'], 404);
            }

            $this->json(['success' => true, 'id_vehiculo' => $vehId, 'estado' => $estado]);
        } catch (Exception $e) {
            error_log("Error actualizando estado del vehiculo: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error al actualizar el estado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Registra una asignacion de transporte para una propiedad.
     */
    public function asignarTransporte(): void {
        $admin = $this->requireAdmin();
        $idProp = (int) ($_POST['id_propiedad'] ?? 0);
        $idVeh = (int) ($_POST['id_vehiculo'] ?? 0);
        $idCita = (int) ($_POST['id_cita'] ?? 0);
        $fechaSalida = trim((string) ($_POST['fecha_salida'] ?? ''));
        $fechaLlegada = trim((string) ($_POST['fecha_llegada'] ?? ''));
        $estado = strtoupper(trim((string) ($_POST['estado'] ?? 'PROGRAMADA')));
        $obs = trim((string) ($_POST['observaciones'] ?? ''));

        if ($idProp <= 0 || $idVeh <= 0 || $fechaSalida === '' || $fechaLlegada === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos: propiedad, vehiculo y fechas son requeridos.'], 400);
        }

        $db = Database::getConnection();
        if ($db === null) {
            $this->json(['success' => false, 'message' => 'Sin conexión con la base de datos.'], 500);
        }

        try {
            $ins = $db->prepare("INSERT INTO asignacion_transporte (id_propiedad, id_vehiculo, id_cita, fecha_salida, fecha_llegada, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$idProp, $idVeh, $idCita > 0 ? $idCita : null, $fechaSalida, $fechaLlegada, $estado, $obs]);

            $this->json(['success' => true, 'id_asignacion' => (int) $db->lastInsertId(), 'estado' => $estado]);
        } catch (Exception $e) {
            error_log("Error asignando transporte: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error al asignar transporte: ' . $e->getMessage()], 500);
        }
    }
}

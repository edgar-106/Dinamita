<!-- =========================================================
     PANEL DE ADMINISTRACIÓN - GESTIÓN DE RESERVAS
     Listado de todas las reservas (citas) con posibilidad
     de cambiar el estado desde el panel.
     ========================================================= -->
<section class="admin-hero">
    <div class="admin-hero-text">
        <p class="admin-kicker">Administración</p>
        <h1 class="admin-title">Gestión de Reservas</h1>
        <p class="admin-sub">Visualiza y controla todas las reservas registradas sobre las propiedades.</p>
    </div>
    <a class="admin-btn-primary" href="<?= BASE_URL ?>admin">← Volver al panel</a>
</section>

<section class="admin-panel">
    <div class="admin-panel-header"><h2>Solicitudes de contacto y asesoría</h2></div>
    <?php if (empty($solicitudes)): ?>
        <p class="admin-empty">No hay solicitudes de contacto registradas.</p>
    <?php else: ?>
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Propiedad</th><th>Mensaje</th><th>Horario</th></tr></thead>
            <tbody>
            <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                    <?php foreach (['nombre', 'telefono', 'correo', 'propiedad', 'mensaje', 'horario'] as $campo): ?>
                        <td><?= htmlspecialchars((string) ($solicitud[$campo] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Reservas (<?= (int) count($reservas) ?>)</h2>
    </div>

    <?php if (empty($reservas)): ?>
        <p class="admin-empty">No hay reservas registradas por el momento.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Propiedad</th>
                        <th>Operación</th>
                        <th>Cliente</th>
                        <th>Asesor</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr data-cita-id="<?= (int) ($reserva['id_cita'] ?? 0) ?>">
                            <td><?= (int) ($reserva['id_cita'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($reserva['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($reserva['hora'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($reserva['propiedad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <br>
                                <small><?= htmlspecialchars((string) ($reserva['ubicacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars((string) ($reserva['operacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($reserva['cliente_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string) ($reserva['cliente_apellido'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <br>
                                <small><?= htmlspecialchars((string) ($reserva['cliente_correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><?= htmlspecialchars((string) ($reserva['asesor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="admin-badge admin-badge-<?= rawurlencode((string) ($reserva['estado'] ?? '')) ?>"><?= htmlspecialchars((string) ($reserva['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <form class="admin-estado-form" data-cita-id="<?= (int) ($reserva['id_cita'] ?? 0) ?>">
                                    <select class="admin-estado-select" name="estado" data-cita-id="<?= (int) ($reserva['id_cita'] ?? 0) ?>">
                                        <?php foreach (($estados_citas ?? []) as $estado): ?>
                                            <option value="<?= htmlspecialchars((string) ($estado['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($reserva['estado'] ?? '')) === ((string) ($estado['nombre'] ?? '')) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) ($estado['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="admin-btn-cambiar" data-cita-id="<?= (int) ($reserva['id_cita'] ?? 0) ?>" onclick="adminCambiarEstado(this.getAttribute('data-cita-id'), this.closest('form').querySelector('select').value)">Actualizar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

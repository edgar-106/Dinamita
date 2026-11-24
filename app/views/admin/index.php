<!-- =========================================================
     PANEL DE ADMINISTRACIÓN - INICIO
     ========================================================= -->
<div class="admin-shell">
<section class="admin-hero">
    <div class="admin-hero-text">
        <p class="admin-kicker">Panel de control</p>
        <h1 class="admin-title">Panel de Administración</h1>
        <p class="admin-sub">Bienvenido al área de gestión de INFONATEC. Controla reservas, solicitudes de contacto, pagos y operaciones inmobiliarias.</p>
    </div>
    <a class="admin-btn-primary" href="<?= BASE_URL ?>admin/reservas">📋 Ver reservas</a>
</section>

<section class="admin-kpis">
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-reservas-total"><?= (int) ($kpis['reservas_totales'] ?? 0) ?></span>
        <span class="admin-kpi-label">Reservas totales</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-reservas-pendientes"><?= (int) ($kpis['reservas_pendientes'] ?? 0) ?></span>
        <span class="admin-kpi-label">Pendientes</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-reservas-confirmadas"><?= (int) ($kpis['reservas_confirmadas'] ?? 0) ?></span>
        <span class="admin-kpi-label">Confirmadas</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-pagos-pendientes"><?= (int) ($kpis['pagos_pendientes'] ?? 0) ?></span>
        <span class="admin-kpi-label">Pagos por validar</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-propiedades-disponibles"><?= (int) ($kpis['propiedades_disponibles'] ?? 0) ?></span>
        <span class="admin-kpi-label">Propiedades disponibles</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-solicitudes-nuevas"><?= (int) ($kpis['solicitudes_nuevas'] ?? 0) ?></span>
        <span class="admin-kpi-label">Solicitudes nuevas</span>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Reservas recientes</h2>
        <a class="admin-btn-secondary" href="<?= BASE_URL ?>admin/reservas">Ver todas las reservas →</a>
    </div>
    <?php if (empty($reservasRecientes)): ?>
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
                        <th>Cliente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservasRecientes as $reserva): ?>
                        <tr>
                            <td><?= (int) ($reserva['id_cita'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($reserva['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($reserva['hora'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($reserva['propiedad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($reserva['cliente_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string) ($reserva['cliente_apellido'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <br>
                                <small><?= htmlspecialchars((string) ($reserva['cliente_correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td><span class="admin-badge admin-badge-<?= rawurlencode((string) ($reserva['estado'] ?? '')) ?>"><?= htmlspecialchars((string) ($reserva['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Solicitudes de contacto y asesoría</h2>
    </div>
    <?php if (empty($solicitudesRecientes)): ?>
        <p class="admin-empty">No hay solicitudes de contacto registradas por el momento.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Interesado</th>
                        <th>Contacto</th>
                        <th>Propiedad / Horario</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudesRecientes as $sol): ?>
                        <tr>
                            <td><?= (int) ($sol['id_solicitud'] ?? 0) ?></td>
                            <td><span class="admin-badge admin-badge-PENDIENTE"><?= htmlspecialchars((string) ($sol['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars((string) ($sol['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($sol['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($sol['correo'])): ?>
                                <br><small><?= htmlspecialchars((string) $sol['correo'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars((string) ($sol['propiedad'] ?? 'Asesoría general'), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($sol['horario_preferido'])): ?>
                                <br><small><?= htmlspecialchars((string) $sol['horario_preferido'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) ($sol['mensaje'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><small><?= htmlspecialchars((string) ($sol['fecha_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Acciones rápidas</h2>
    </div>
    <div class="admin-actions-grid">
        <a class="admin-action-card" href="<?= BASE_URL ?>admin/reservas">📋 Gestión de reservas</a>
        <a class="admin-action-card" href="<?= BASE_URL ?>admin/pagos">💳 Validación de pagos</a>
        <a class="admin-action-card" href="<?= BASE_URL ?>admin/eventos">🎯 Eventos y Recorridos</a>
        <a class="admin-action-card" href="<?= BASE_URL ?>admin/logistica">🚚 Logística de Transporte</a>
        <a class="admin-action-card" href="<?= BASE_URL ?>explorar">🏠 Ver catálogo público</a>
        <a class="admin-action-card" href="<?= BASE_URL ?>vender">📝 Publicar propiedad</a>
    </div>
</section>
</div>

<!-- =========================================================
     PANEL DE ADMINISTRACIÓN - VALIDACIÓN DE PAGOS
     Listado de transacciones/pagos registrados con comprobante
     y acción de confirmación administrativa.
     ========================================================= -->
<section class="admin-hero">
    <div class="admin-hero-text">
        <p class="admin-kicker">Administración</p>
        <h1 class="admin-title">Validación de Pagos</h1>
        <p class="admin-sub">Revisa los comprobantes de pago recibidos y confirma los pagos para liberar propiedades.</p>
    </div>
    <a class="admin-btn-primary" href="<?= BASE_URL ?>admin">← Volver al panel</a>
</section>

<section class="admin-kpis">
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-pagos-pendientes"><?= (int) ($pagos_pendientes ?? 0) ?></span>
        <span class="admin-kpi-label">Pagos por validar</span>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Transacciones / Pagos (<?= (int) count($pagos) ?>)</h2>
    </div>

    <?php if (empty($pagos)): ?>
        <p class="admin-empty">No hay pagos registrados por el momento.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Propiedad</th>
                        <th>Comprador</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                        <tr data-transaccion-id="<?= (int) ($pago['id_transaccion'] ?? 0) ?>">
                            <td><?= (int) ($pago['id_transaccion'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($pago['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($pago['propiedad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <br>
                                <small><?= htmlspecialchars((string) ($pago['ubicacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars((string) ($pago['comprador_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string) ($pago['comprador_apellido'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <br>
                                <small><?= htmlspecialchars((string) ($pago['comprador_correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>$<?= number_format((float) ($pago['monto_pago'] ?? 0), 2) ?></td>
                            <td><?= htmlspecialchars((string) ($pago['metodo_pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($pago['referencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="admin-badge admin-badge-<?= rawurlencode((string) ($pago['estado_pago'] ?? '')) ?>">
                                    <?= htmlspecialchars((string) ($pago['estado_pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ((string) ($pago['estado_pago'] ?? '') === 'PENDIENTE'): ?>
                                    <button type="button" class="admin-btn-cambiar" data-transaccion-id="<?= (int) ($pago['id_transaccion'] ?? 0) ?>" onclick="adminConfirmarPago(this.getAttribute('data-transaccion-id'))">
                                        Confirmar pago
                                    </button>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-CONFIRMADO">Validado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

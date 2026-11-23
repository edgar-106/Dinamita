<!-- =========================================================
     PANEL DE ADMINISTRACIÓN - LOGÍSTICA DE TRANSPORTE
     Gestión de vehículos y asignaciones de traslado.
     ========================================================= -->
<section class="admin-hero">
    <div class="admin-hero-text">
        <p class="admin-kicker">Administración</p>
        <h1 class="admin-title">Logística de Transporte</h1>
        <p class="admin-sub">Gestiona el parque vehicular y las asignaciones de traslado para visitas y tours inmobiliarios.</p>
    </div>
    <a class="admin-btn-primary" href="<?= BASE_URL ?>admin">← Volver al panel</a>
</section>

<section class="admin-kpis">
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-vehiculos-totales"><?= (int) ($kpis['vehiculos_totales'] ?? 0) ?></span>
        <span class="admin-kpi-label">Vehículos registrados</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-vehiculos-disponibles"><?= (int) ($kpis['vehiculos_disponibles'] ?? 0) ?></span>
        <span class="admin-kpi-label">Vehículos disponibles</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-vehiculos-en-uso"><?= (int) ($kpis['vehiculos_en_uso'] ?? 0) ?></span>
        <span class="admin-kpi-label">Vehículos en uso</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-asignaciones-activas"><?= (int) ($kpis['asignaciones_activas'] ?? 0) ?></span>
        <span class="admin-kpi-label">Asignaciones activas</span>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Parque vehicular (<?= (int) count($vehiculos) ?>)</h2>
    </div>

    <?php if (empty($vehiculos)): ?>
        <p class="admin-empty">No hay vehículos registrados en el parque de transporte.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Placa</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Capacidad</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehiculos as $vehiculo): ?>
                        <tr data-vehiculo-id="<?= (int) ($vehiculo['id_vehiculo'] ?? 0) ?>">
                            <td><?= (int) ($vehiculo['id_vehiculo'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($vehiculo['placa'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($vehiculo['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($vehiculo['marca'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($vehiculo['modelo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) ($vehiculo['capacidad'] ?? 0) ?></td>
                            <td>
                                <span class="admin-badge admin-badge-<?= rawurlencode((string) ($vehiculo['estado'] ?? '')) ?>"><?= htmlspecialchars((string) ($vehiculo['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <form class="admin-estado-form" data-vehiculo-id="<?= (int) ($vehiculo['id_vehiculo'] ?? 0) ?>">
                                    <select class="admin-estado-select" name="estado" data-vehiculo-id="<?= (int) ($vehiculo['id_vehiculo'] ?? 0) ?>">
                                        <option value="DISPONIBLE" <?= ((string) ($vehiculo['estado'] ?? '')) === 'DISPONIBLE' ? 'selected' : '' ?>>DISPONIBLE</option>
                                        <option value="EN_USO" <?= ((string) ($vehiculo['estado'] ?? '')) === 'EN_USO' ? 'selected' : '' ?>>EN_USO</option>
                                        <option value="MANTENIMIENTO" <?= ((string) ($vehiculo['estado'] ?? '')) === 'MANTENIMIENTO' ? 'selected' : '' ?>>MANTENIMIENTO</option>
                                    </select>
                                    <button type="button" class="admin-btn-cambiar" data-vehiculo-id="<?= (int) ($vehiculo['id_vehiculo'] ?? 0) ?>" onclick="adminCambiarEstadoVehiculo(this.getAttribute('data-vehiculo-id'), this.closest('form').querySelector('select').value)">Actualizar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Registrar vehículo</h2>
    </div>
    <form class="admin-form" onsubmit="adminCrearVehiculo(event)">
        <div class="admin-form-grid">
            <div class="form-group">
                <label for="vehPlaca">Placa</label>
                <input type="text" id="vehPlaca" name="placa" placeholder="ABC-1234" required>
            </div>
            <div class="form-group">
                <label for="vehTipo">Tipo</label>
                <select id="vehTipo" name="tipo" required>
                    <option value="AUTO">AUTO</option>
                    <option value="VAN">VAN</option>
                    <option value="CAMION">CAMION</option>
                    <option value="TRACTOR">TRACTOR</option>
                </select>
            </div>
            <div class="form-group">
                <label for="vehMarca">Marca</label>
                <input type="text" id="vehMarca" name="marca" placeholder="Ej. Toyota">
            </div>
            <div class="form-group">
                <label for="vehModelo">Modelo</label>
                <input type="text" id="vehModelo" name="modelo" placeholder="Ej. Hilux">
            </div>
            <div class="form-group">
                <label for="vehCapacidad">Capacidad (personas)</label>
                <input type="number" id="vehCapacidad" name="capacidad" min="1" placeholder="Ej. 8">
            </div>
            <div class="form-group">
                <label for="vehEstado">Estado</label>
                <select id="vehEstado" name="estado">
                    <option value="DISPONIBLE">DISPONIBLE</option>
                    <option value="EN_USO">EN_USO</option>
                    <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                </select>
            </div>
            <div class="form-group full">
                <label for="vehObs">Observaciones</label>
                <textarea id="vehObs" name="observaciones" rows="2"></textarea>
            </div>
        </div>
        <button type="submit" class="admin-btn-cambiar" style="margin-top:16px;">Registrar vehículo</button>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Asignar transporte (<?= (int) count($asignaciones) ?>)</h2>
    </div>

    <?php if (!empty($asignaciones)): ?>
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>Propiedad</th><th>Vehículo</th><th>Salida</th><th>Llegada</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($asignaciones as $asignacion): ?>
                <tr>
                    <?php foreach (['propiedad', 'vehiculo_placa', 'fecha_salida', 'fecha_llegada', 'estado'] as $campo): ?>
                        <td><?= htmlspecialchars((string) ($asignacion[$campo] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    <form class="admin-form" onsubmit="adminAsignarTransporte(event)">
        <div class="admin-form-grid">
            <div class="form-group">
                <label for="transPropiedad">Propiedad destino</label>
                <select id="transPropiedad" name="id_propiedad" required>
                    <?php foreach (($propiedades ?? []) as $prop): ?>
                        <option value="<?= (int) ($prop['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($prop['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="transVehiculo">Vehículo</label>
                <select id="transVehiculo" name="id_vehiculo" required>
                    <?php foreach (($vehiculos ?? []) as $vehiculo): ?>
                        <option value="<?= (int) ($vehiculo['id_vehiculo'] ?? 0) ?>" data-placa="<?= htmlspecialchars((string) ($vehiculo['placa'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($vehiculo['placa'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="transFechaSalida">Fecha salida</label>
                <input type="datetime-local" id="transFechaSalida" name="fecha_salida" required>
            </div>
            <div class="form-group">
                <label for="transFechaLlegada">Fecha llegada</label>
                <input type="datetime-local" id="transFechaLlegada" name="fecha_llegada" required>
            </div>
            <div class="form-group">
                <label for="transEstado">Estado</label>
                <select id="transEstado" name="estado">
                    <option value="PROGRAMADA">PROGRAMADA</option>
                    <option value="EN_CAMINO">EN_CAMINO</option>
                    <option value="COMPLETADA">COMPLETADA</option>
                    <option value="CANCELADA">CANCELADA</option>
                </select>
            </div>
            <div class="form-group full">
                <label for="transObs">Observaciones</label>
                <textarea id="transObs" name="observaciones" rows="2"></textarea>
            </div>
        </div>
        <button type="submit" class="admin-btn-cambiar" style="margin-top:16px;">Asignar transporte</button>
    </form>
</section>

<!-- =========================================================
     PANEL DE ADMINISTRACIÓN - EVENTOS Y RECORRIDOS
     Gestión de atracciones (recorridos virtuales 3D) y coordinación
     de tours / actividades inmobiliarias.
     ========================================================= -->
<section class="admin-hero">
    <div class="admin-hero-text">
        <p class="admin-kicker">Administración</p>
        <h1 class="admin-title">Eventos y Recorridos</h1>
        <p class="admin-sub">Gestiona las atracciones con recorridos virtuales 3D y la coordinación de tours y actividades inmobiliarias.</p>
    </div>
    <a class="admin-btn-primary" href="<?= BASE_URL ?>admin">← Volver al panel</a>
</section>

<section class="admin-kpis">
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-tours-activos"><?= (int) ($kpis['tours_activos'] ?? 0) ?></span>
        <span class="admin-kpi-label">Tours 3D activos</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-propiedades-con-tour"><?= (int) ($kpis['propiedades_con_tour'] ?? 0) ?></span>
        <span class="admin-kpi-label">Propiedades con tour</span>
    </div>
    <div class="admin-kpi-card">
        <span class="admin-kpi-value" id="kpi-citas-programadas"><?= (int) ($kpis['citas_programadas'] ?? 0) ?></span>
        <span class="admin-kpi-label">Visitas programadas</span>
    </div>
</section>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>Recorridos virtuales 3D (<?= (int) count($tours) ?>)</h2>
    </div>

    <?php if (empty($tours)): ?>
        <p class="admin-empty">No hay recorridos virtuales registrados por el momento.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Propiedad</th>
                        <th>Ubicación</th>
                        <th>Tour 3D (URL)</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tours as $tour): ?>
                        <tr data-paseo-id="<?= (int) ($tour['id_paseo'] ?? 0) ?>">
                            <td><?= (int) ($tour['id_paseo'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($tour['propiedad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($tour['ubicacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><small><?= htmlspecialchars((string) ($tour['url_3d'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td><?= htmlspecialchars((string) ($tour['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($tour['fecha_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <button type="button" class="admin-btn-cambiar" data-paseo-id="<?= (int) ($tour['id_paseo'] ?? 0) ?>" onclick="adminEliminarTour(this.getAttribute('data-paseo-id'))">Eliminar</button>
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
        <h2>Registrar nuevo recorrido 3D (atracción)</h2>
    </div>
    <form class="admin-form" onsubmit="adminRegistrarTour(event)">
        <div class="admin-form-grid">
            <div class="form-group">
                <label for="tourPropiedad">Propiedad</label>
                <select id="tourPropiedad" name="id_propiedad" required>
                    <?php foreach (($propiedades ?? []) as $prop): ?>
                        <option value="<?= (int) ($prop['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($prop['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label for="tourUrl">URL del recorrido 3D / Tour</label>
                <input type="url" id="tourUrl" name="url_3d" placeholder="https://tours.infonatec.com/..." required>
            </div>
            <div class="form-group full">
                <label for="tourDescripcion">Descripción del recorrido</label>
                <textarea id="tourDescripcion" name="descripcion" rows="3" placeholder="Describe el recorrido virtual..."></textarea>
            </div>
        </div>
        <button type="submit" class="admin-btn-cambiar" style="margin-top:16px;">Registrar tour</button>
    </form>
</section>

<?php
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$prop = $bundle['propiedad'];
$tour = $bundle['recorrido'];
$proc = $bundle['procesador'];
$isPublished = ($tour['estado'] ?? 'BORRADOR') === 'PUBLICADO';
$editable = !empty($prop['editable']);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/recorrido-video.css?v=1.0">

<main id="main-content" class="rv-page">
    <a class="rv-breadcrumb" href="<?= BASE_URL ?>mis-propiedades">← Volver a Mis propiedades</a>

    <header class="rv-header">
        <div>
            <p class="owner-eyebrow" style="margin:0 0 4px;">Estudio de recorrido por escenas</p>
            <h1 style="margin:0 0 6px;"><?= $esc($prop['titulo']) ?></h1>
            <p class="rv-muted" style="margin:0;"><?= $esc($prop['ubicacion']) ?> · Estado del anuncio: <?= $esc($prop['estado']) ?></p>
        </div>
        <div class="rv-header-actions">
            <span id="rvTourBadge" class="rv-badge <?= $isPublished ? 'rv-badge-published' : 'rv-badge-draft' ?>">
                <?= $esc($tour['estado']) ?>
            </span>
            <?php if ($editable): ?>
            <button type="button" id="btnToggleTourState" class="btn-primary">
                <?= $isPublished ? 'Pasar a borrador' : 'Publicar recorrido' ?>
            </button>
            <?php endif; ?>
            <a id="linkPreviewTour" class="btn-outline" href="<?= BASE_URL ?>recorrido/<?= (int) $prop['id'] ?>" target="_blank" rel="noopener">
                Vista previa como visitante
            </a>
        </div>
    </header>

    <div id="rvProcessorBanner" class="rv-banner <?= !empty($proc['ffmpeg_available']) ? 'rv-banner-ok' : 'rv-banner-warn' ?>">
        <strong>Procesamiento de video:</strong>
        <?= $esc($proc['message']) ?>
        <small style="display:block;margin-top:4px;">
            Formatos admitidos: <strong>MP4, MOV, WebM</strong> · Tamaño máximo por video: <strong><?= $esc($proc['max_video_mb']) ?> MB</strong>.
            Los videos originales quedan protegidos fuera de la carpeta pública.
        </small>
    </div>

    <div id="rvGlobalStatus" class="rv-banner rv-banner-info" role="status" hidden></div>

    <?php if (!$editable): ?>
        <div class="rv-banner rv-banner-warn">
            Esta propiedad ya no está disponible para editar. Solo puedes consultar el estado del recorrido.
        </div>
    <?php endif; ?>

    <div class="rv-grid">
        <!-- COLUMNA IZQUIERDA: CARGA DE VIDEO Y EXTRACCIÓN DE ESCENAS -->
        <section>
            <article class="rv-card">
                <h2>1. Subir video convencional</h2>
                <p class="rv-muted">Sube un video grabado con celular recorriendo los espacios del inmueble.</p>

                <?php if ($editable): ?>
                <form id="formUploadVideo" class="rv-upload-box">
                    <div class="rv-field" style="margin-bottom:10px;">
                        <label for="inputVideoFile">Archivo de video (MP4, MOV o WebM, máx. <?= $esc($proc['max_video_mb']) ?> MB)</label>
                        <input type="file" id="inputVideoFile" name="video" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm" required>
                    </div>
                    <button type="submit" id="btnUploadVideo" class="btn-primary">Subir video protegido</button>
                    <div id="uploadProgressWrap" class="rv-progress-wrap" hidden>
                        <div id="uploadProgressBar" class="rv-progress-bar"></div>
                    </div>
                    <small id="uploadProgressText" class="rv-muted" style="display:block;margin-top:6px;"></small>
                </form>
                <?php endif; ?>

                <div id="listaVideos" class="rv-video-list"></div>
            </article>

            <article class="rv-card" id="cardExtractor">
                <h2>2. Seleccionar momento y crear escena</h2>
                <p class="rv-muted">Reproduce el video, ubica el instante del espacio, asigna un nombre manual y define el fragmento opcional.</p>

                <div id="sinVideoActivo" class="rv-banner rv-banner-info">
                    Sube o selecciona un video en el paso 1 para comenzar a capturar escenas.
                </div>

                <div id="extractorWorkspace" hidden>
                    <video id="studioVideoPlayer" class="rv-player" controls playsinline preload="metadata" crossorigin="use-credentials"></video>

                    <div class="rv-controls-row">
                        <button type="button" class="rv-btn-sm" data-seek="-1">-1s</button>
                        <button type="button" class="rv-btn-sm" data-seek="-0.5">-0.5s</button>
                        <button type="button" class="rv-btn-sm" data-seek="0.5">+0.5s</button>
                        <button type="button" class="rv-btn-sm" data-seek="1">+1s</button>
                        <span id="studioTimeLabel" style="font-size:13px;font-weight:700;color:#0f172a;margin-left:auto;">0:00.00 / 0:00.00</span>
                    </div>

                    <div class="rv-controls-row">
                        <button type="button" class="rv-btn-sm" id="btnSetMoment">Usar momento actual</button>
                        <button type="button" class="rv-btn-sm" id="btnSetStart">Marcar inicio</button>
                        <button type="button" class="rv-btn-sm" id="btnSetEnd">Marcar fin</button>
                        <button type="button" class="rv-btn-sm" id="btnTestFragment">▶ Probar fragmento</button>
                    </div>

                    <form id="formCreateScene" style="margin-top:12px;">
                        <div class="rv-field">
                            <label for="sceneNombre">Nombre de la escena (manual)</label>
                            <input type="text" id="sceneNombre" name="nombre" maxlength="120" placeholder="Ej. Sala principal, Cocina, Patio..." required>
                        </div>
                        <div class="rv-chips" aria-label="Nombres rápidos">
                            <?php foreach (['Sala', 'Cocina', 'Comedor', 'Habitación', 'Baño', 'Patio', 'Fachada', 'Terraza'] as $chip): ?>
                                <button type="button" class="rv-chip" data-chip="<?= $esc($chip) ?>"><?= $esc($chip) ?></button>
                            <?php endforeach; ?>
                        </div>

                        <div class="rv-form-row">
                            <div class="rv-field">
                                <label for="sceneMoment">Momento foto (seg)</label>
                                <input type="number" id="sceneMoment" name="momento_segundos" min="0" step="0.01" value="0" required>
                            </div>
                            <div class="rv-field">
                                <label for="sceneStart">Inicio fragmento (opcional)</label>
                                <input type="number" id="sceneStart" name="inicio_segundos" min="0" step="0.01" placeholder="Ej. 2.0">
                            </div>
                            <div class="rv-field">
                                <label for="sceneEnd">Fin fragmento (opcional)</label>
                                <input type="number" id="sceneEnd" name="fin_segundos" min="0.01" step="0.01" placeholder="Ej. 8.5">
                            </div>
                        </div>

                        <?php if ($editable): ?>
                        <button type="submit" id="btnSaveScene" class="btn-primary">Extraer imagen y guardar escena</button>
                        <?php endif; ?>
                    </form>
                </div>
            </article>
        </section>

        <!-- COLUMNA DERECHA: ESCENAS, CONEXIONES COMPARTIDAS Y CONTRATO -->
        <section>
            <article class="rv-card">
                <h2>3. Escenas del recorrido (<span id="countEscenas">0</span>)</h2>
                <p class="rv-muted">Escenas planas (`tipo_visual: "imagen"`) generadas desde tus videos y listas para conectar.</p>
                <div id="listaEscenas" class="rv-scene-list"></div>
            </article>

            <article class="rv-card">
                <h2>4. Editor compartido de conexiones</h2>
                <p class="rv-muted">Selecciona una escena de origen, haz clic sobre su imagen para colocar el punto interactivo (`plano_xy`) y elige hacia qué escena lleva.</p>

                <div id="sinConexionesPosibles" class="rv-banner rv-banner-info">
                    Necesitas al menos <strong>2 escenas</strong> guardadas para crear conexiones entre espacios.
                </div>

                <div id="editorConexionesBox" hidden>
                    <div class="rv-form-row" style="grid-template-columns:1fr 1fr;">
                        <div class="rv-field">
                            <label for="connOriginSelect">Escena origen</label>
                            <select id="connOriginSelect"></select>
                        </div>
                        <div class="rv-field">
                            <label for="connTargetSelect">Escena destino</label>
                            <select id="connTargetSelect"></select>
                        </div>
                    </div>

                    <div class="rv-stage-outer">
                        <div id="connStageWrap" class="rv-stage-wrap" title="Haz clic sobre la imagen para ubicar el punto interactivo">
                            <img id="connStageImg" class="rv-stage-img" src="" alt="Vista de la escena origen">
                            <div id="connPinsLayer"></div>
                        </div>
                    </div>
                    <small id="connCoordsHint" class="rv-muted" style="display:block;margin-top:6px;">
                        Coordenada seleccionada: <strong>x: 50%, y: 50%</strong> (haz clic en la imagen para moverla).
                    </small>

                    <?php if ($editable): ?>
                    <form id="formCreateConnection" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                        <div class="rv-field" style="flex:1;min-width:180px;">
                            <label for="connLabel">Etiqueta del punto interactivo</label>
                            <input type="text" id="connLabel" maxlength="120" placeholder="Ej. Ir a Cocina" required>
                        </div>
                        <button type="submit" class="btn-primary">Agregar conexión</button>
                    </form>
                    <?php endif; ?>

                    <div id="listaConexionesOrigen" class="rv-video-list" style="margin-top:12px;"></div>
                </div>
            </article>

            <article class="rv-card">
                <details>
                    <summary style="cursor:pointer;font-weight:700;color:#0f172a;">Contrato JSON de integración (compartido con módulo de fotografías)</summary>
                    <p class="rv-muted" style="margin-top:8px;">Salida normalizada mediante <code>window.InfonatecTourContract</code>.</p>
                    <pre id="rvContractJson" class="rv-json-box"></pre>
                </details>
            </article>
        </section>
    </div>
</main>

<script src="<?= BASE_URL ?>js/recorrido-contrato.js?v=1.0"></script>
<script>
    window.RECORRIDO_STUDIO_INIT = <?= json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>js/recorrido-video.js?v=1.0"></script>

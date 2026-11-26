<?php $esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); $isPreview=!$editor && (($_GET['preview']??'')==='1'); ?>
<?php if(!$editor): ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $esc($pageTitle) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>css/styles.css?v=6.5">
<script>window.APP_BASE_URL=<?= json_encode(BASE_URL,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
</head><body class="photo-tour-standalone">
<?php endif; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>tour/lib/pannellum.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/recorridos-fotos.css?v=4">
<link rel="stylesheet" href="<?= BASE_URL ?>css/recorrido-video.css?v=2">
<main id="main-content" class="photo-tour <?= $editor?'is-editor':'' ?>">
    <header class="pt-heading"><div><a class="pt-back" href="<?= BASE_URL ?><?= $editor?'mis-propiedades':'explorar' ?>" <?= !$editor?'target="_top"':'' ?>>← <?= $editor?'Mis propiedades':'Explorar' ?></a><p class="pt-eyebrow"><?= $editor?'ESTUDIO DE RECORRIDOS':'RECORRIDO INTERACTIVO' ?></p><h1><?= $esc($propertyTitle) ?></h1>
        <p class="pt-intro"><?= $editor?'Combina fotografías, panorámicas y escenas extraídas desde video.':'Descubre la propiedad a tu ritmo.' ?></p></div>
        <?php if($editor): ?><div class="pt-actions"><button id="ptSave" type="button">Guardar borrador</button><button id="ptPublish" class="pt-primary" type="button">Publicar recorrido</button><button id="ptHide" type="button">Ocultar recorrido</button><a href="<?= BASE_URL ?>recorridos/<?= $propertyId ?>?preview=1" target="_blank" rel="noopener" id="ptPreview">Vista previa guardada ↗</a></div><?php endif; ?>
    </header>
    <p id="ptStatus" role="status" aria-live="polite"></p>
    <?php if($editor): ?>
    <section id="ptVideoPanel" class="pt-video-panel" hidden aria-label="Estudio de video para extraer escenas">
        <div class="pt-video-head">
            <div>
                <p class="pt-eyebrow" style="margin:0 0 4px;">DESDE VIDEO CONVENCIONAL</p>
                <h2>Extraer escenas y fragmentos de video</h2>
            </div>
            <div class="pt-video-head-actions">
                <span id="ptVideoProcessorBadge" class="pt-video-pill">Comprobando procesador…</span>
                <button id="ptCloseVideoStudio" type="button">Cerrar panel</button>
            </div>
        </div>
        <p id="ptVideoStatus" class="pt-video-status" role="status" aria-live="polite"></p>
        <div class="pt-video-grid">
            <div class="pt-video-library">
                <h3>1. Videos privados del inmueble</h3>
                <p class="pt-hint" style="margin-top:4px;">El video original permanece protegido en almacenamiento privado y nunca se expone sin permiso.</p>
                <input id="ptVideoFile" type="file" accept="video/mp4,video/quicktime,video/webm" hidden>
                <div class="pt-video-upload-row">
                    <button id="ptVideoUploadBtn" type="button" class="pt-primary">Subir video (MP4 / MOV / WebM)</button>
                    <small id="ptVideoLimitHint" class="pt-hint" style="margin:0;"></small>
                </div>
                <div id="ptVideoProgressWrap" class="rv-progress-wrap" hidden>
                    <div id="ptVideoProgressBar" class="rv-progress-bar"></div>
                </div>
                <p id="ptVideoProgressText" class="pt-hint" style="margin:6px 0 0;"></p>
                <div id="ptVideoList" class="rv-video-list"></div>
            </div>
            <div class="pt-video-extractor">
                <h3>2. Selecciona momento, nombre y fragmento</h3>
                <div id="ptVideoEmpty" class="pt-video-empty">
                    Sube o elige uno de tus videos para reproducirlo y extraer escenas al recorrido compartido.
                </div>
                <div id="ptVideoWorkspace" hidden>
                    <video id="ptVideoPlayer" class="rv-player" controls playsinline preload="metadata"></video>
                    <div class="rv-controls-row">
                        <span id="ptVideoTime" class="pt-video-time">0:00.00 / 0:00.00</span>
                        <button type="button" class="rv-btn-sm" data-seek="-1">-1 s</button>
                        <button type="button" class="rv-btn-sm" data-seek="-0.5">-0.5 s</button>
                        <button type="button" class="rv-btn-sm" data-seek="0.5">+0.5 s</button>
                        <button type="button" class="rv-btn-sm" data-seek="1">+1 s</button>
                    </div>
                    <div class="rv-controls-row">
                        <button type="button" id="ptMarkStart" class="rv-btn-sm">Usar como inicio</button>
                        <button type="button" id="ptMarkEnd" class="rv-btn-sm">Usar como fin</button>
                        <button type="button" id="ptTestRange" class="rv-btn-sm">Probar fragmento</button>
                        <button type="button" id="ptClearRange" class="rv-btn-sm">Limpiar</button>
                    </div>
                    <div class="rv-field" style="margin-bottom:10px;">
                        <label for="ptVideoSceneName">Nombre del espacio</label>
                        <input id="ptVideoSceneName" type="text" maxlength="80" placeholder="Ej. Sala, Cocina, Habitación">
                    </div>
                    <div class="rv-chips" aria-label="Nombres rápidos">
                        <button type="button" class="rv-chip" data-scene-chip="Sala">Sala</button>
                        <button type="button" class="rv-chip" data-scene-chip="Cocina">Cocina</button>
                        <button type="button" class="rv-chip" data-scene-chip="Comedor">Comedor</button>
                        <button type="button" class="rv-chip" data-scene-chip="Habitación">Habitación</button>
                        <button type="button" class="rv-chip" data-scene-chip="Baño">Baño</button>
                        <button type="button" class="rv-chip" data-scene-chip="Patio">Patio</button>
                        <button type="button" class="rv-chip" data-scene-chip="Fachada">Fachada</button>
                        <button type="button" class="rv-chip" data-scene-chip="Terraza">Terraza</button>
                    </div>
                    <div class="pt-video-range-row">
                        <div class="rv-field">
                            <label for="ptVideoStartInput">Inicio fragmento (s)</label>
                            <input id="ptVideoStartInput" type="number" min="0" step="0.01" placeholder="Opcional">
                        </div>
                        <div class="rv-field">
                            <label for="ptVideoEndInput">Fin fragmento (s)</label>
                            <input id="ptVideoEndInput" type="number" min="0.01" step="0.01" placeholder="Opcional">
                        </div>
                    </div>
                    <button type="button" id="ptExtractSceneBtn" class="pt-primary" style="width:100%;margin-top:8px;">Extraer escena al recorrido</button>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <div class="pt-layout">
        <aside class="pt-sidebar"><div class="pt-panel-title"><h2>Tu recorrido</h2><span id="ptSceneCount">0 espacios</span></div><ol id="ptScenes"></ol>
        <?php if($editor): ?><label class="pt-upload"><span class="pt-upload-icon" aria-hidden="true">＋</span><strong>Agregar fotografía</strong><span>JPG, PNG o WEBP · hasta 8 MB</span><input id="ptUpload" type="file" aria-label="Agregar fotografía" accept="image/jpeg,image/png,image/webp"></label>
            <button id="ptOpenVideoStudio" class="pt-video-trigger" type="button"><span aria-hidden="true">🎬</span><strong>Extraer desde video</strong><span>MP4, MOV o WebM · elige escenas</span></button>
            <p class="pt-hint">Hasta 30 espacios. Puedes combinar fotos normales, panorámicas horizontales y escenas desde video.</p>
            <button id="ptCleanup" class="pt-quiet" type="button">Limpiar imágenes sin usar</button>
        <?php endif; ?></aside>
        <section class="pt-workspace" aria-label="Escena seleccionada">
            <div class="pt-stage">
            <div class="pt-scene-heading"><h2 id="ptTitle">Agrega tu primer espacio</h2><span id="ptStartLabel"></span></div>
            <p id="ptInstructions" class="pt-hint">Selecciona una fotografía para comenzar.</p>
            <div id="ptViewport" class="pt-viewport" tabindex="0" aria-label="Fotografía del espacio; desplaza horizontalmente las panorámicas">
                <div id="ptCanvas" class="pt-canvas"><img id="ptImage" alt="" hidden><div id="ptPoints"></div></div>
                <div id="ptEmpty"><span aria-hidden="true">▧</span><h3>Un recorrido empieza aquí</h3><p>Agrega una fotografía o extrae escenas desde un video para construir el recorrido.</p></div>
            </div>
            <div id="ptPanorama" class="pt-panorama" hidden tabindex="0" aria-label="Visor panorámico: arrastra para mirar alrededor y usa más o menos para acercar"></div>
            <button id="ptFlatView" type="button" hidden>Ver fotografía completa</button>
            <div id="ptSceneVideoBar" class="pt-scene-video-bar" hidden>
                <span id="ptSceneVideoRange" class="pt-scene-video-range"></span>
                <button id="ptPlaySceneVideo" type="button" class="pt-primary">▶ Ver fragmento de video</button>
            </div>
            <div id="ptSceneVideoBox" class="pt-scene-video-box" hidden>
                <video id="ptSceneVideoPlayer" class="rv-player" controls playsinline preload="metadata"></video>
                <div class="pt-scene-video-actions">
                    <button id="ptReplaySceneVideo" type="button">Repetir fragmento</button>
                    <button id="ptCloseSceneVideo" type="button">Ocultar video</button>
                </div>
            </div>
            <div class="pt-connections"><h3>Continúa el recorrido</h3><ul id="ptLinks" class="pt-link-list"></ul></div>
            </div>
            <?php if($editor): ?>
            <aside class="pt-inspector" aria-label="Configuración del espacio"><div class="pt-inspector-title"><span>01</span><h3>El espacio</h3></div>
            <form id="ptSceneForm" class="pt-form" hidden>
                <label>Nombre del espacio<input id="ptName" maxlength="80" required></label>
                <label>Tipo de fotografía<select id="ptType"><option value="imagen">Fotografía normal</option><option value="panorama">Panorámica horizontal</option></select></label>
                <label id="ptCoverageLabel" hidden>Cobertura capturada<select id="ptCoverage"><option value="180">180° · panorámica parcial</option><option value="270">270° · panorámica amplia</option><option value="360">360° · vuelta completa</option></select><span>Elige 360° solo si fotografiaste toda la vuelta. El visor no crea los espacios que no capturaste.</span></label>
                <div id="ptVideoMeta" class="pt-video-meta" hidden>
                    <span class="pt-video-pill">Escena de video (imagen plana)</span>
                    <label>Inicio del fragmento (s)<input id="ptVideoStart" type="number" min="0" step="0.1" placeholder="Opcional"></label>
                    <label>Fin del fragmento (s)<input id="ptVideoEnd" type="number" min="0.1" step="0.1" placeholder="Opcional"></label>
                </div>
                <div class="pt-actions"><button id="ptSetStart" type="button">Usar como inicio</button><button id="ptRemove" type="button">Quitar espacio</button></div>
            </form>
            <form id="ptLinkForm" class="pt-form" hidden>
                <div class="pt-inspector-title"><span>02</span><h3>Conectar espacios</h3></div><p class="pt-hint">Toca un lugar de la foto y elige a dónde llevará. Para regresar, crea otro punto desde el destino.</p>
                <label>Ir al espacio<select id="ptDestination" required></select></label>
                <label>Texto del punto<input id="ptLabel" maxlength="60" placeholder="Ir a la cocina" required></label>
                <details class="pt-position"><summary>Ajustar posición con precisión</summary><label>Posición horizontal (%)<input id="ptX" type="number" min="0" max="100" step="0.1" value="50" required></label>
                <label>Posición vertical (%)<input id="ptY" type="number" min="0" max="100" step="0.1" value="50" required></label></details>
                <button class="pt-primary" type="submit">Agregar conexión</button>
            </form>
            </aside>
            <?php endif; ?>
        </section>
    </div>
</main>
<script id="ptBoot" type="application/json"><?= json_encode(['editor'=>$editor,'preview'=>$isPreview,'propertyId'=>$propertyId,'data'=>$tourData],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= BASE_URL ?>tour/lib/pannellum.js" defer></script>
<script src="<?= BASE_URL ?>js/recorridos-fotos.js?v=4" defer></script>
<?php if($editor): ?><script src="<?= BASE_URL ?>js/recorrido-video.js?v=2" defer></script><?php endif; ?>
<?php if(!$editor): ?></body></html><?php endif; ?>

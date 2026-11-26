<?php $esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); ?>
<?php if(!$editor): ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $esc($pageTitle) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>css/styles.css?v=6.5">
<script>window.APP_BASE_URL=<?= json_encode(BASE_URL,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
</head><body class="photo-tour-standalone">
<?php endif; ?>
<link rel="stylesheet" href="<?= BASE_URL ?>tour/lib/pannellum.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/recorridos-fotos.css?v=4">
<main id="main-content" class="photo-tour <?= $editor?'is-editor':'' ?>">
    <header class="pt-heading"><div><a class="pt-back" href="<?= BASE_URL ?><?= $editor?'mis-propiedades':'explorar' ?>" <?= !$editor?'target="_top"':'' ?>>← <?= $editor?'Mis propiedades':'Explorar' ?></a><p class="pt-eyebrow"><?= $editor?'ESTUDIO DE RECORRIDOS':'RECORRIDO FOTOGRÁFICO' ?></p><h1><?= $esc($propertyTitle) ?></h1>
        <p class="pt-intro"><?= $editor?'Un espacio a la vez. Dale forma al recorrido de tu propiedad.':'Descubre la propiedad a tu ritmo.' ?></p></div>
        <?php if($editor): ?><div class="pt-actions"><button id="ptSave" type="button">Guardar borrador</button><button id="ptPublish" class="pt-primary" type="button">Publicar recorrido</button><button id="ptHide" type="button">Ocultar recorrido</button><a href="<?= BASE_URL ?>recorridos/<?= $propertyId ?>?preview=1" target="_blank" rel="noopener" id="ptPreview">Vista previa guardada ↗</a></div><?php endif; ?>
    </header>
    <p id="ptStatus" role="status" aria-live="polite"></p>
    <div class="pt-layout">
        <aside class="pt-sidebar"><div class="pt-panel-title"><h2>Tu recorrido</h2><span id="ptSceneCount">0 espacios</span></div><ol id="ptScenes"></ol>
        <?php if($editor): ?><label class="pt-upload"><span class="pt-upload-icon" aria-hidden="true">＋</span><strong>Agregar fotografía</strong><span>JPG, PNG o WEBP · hasta 8 MB</span><input id="ptUpload" type="file" aria-label="Agregar fotografía" accept="image/jpeg,image/png,image/webp"></label>
            <p class="pt-hint">Hasta 30 espacios. Puedes combinar fotos normales y panorámicas horizontales.</p>
            <button id="ptCleanup" class="pt-quiet" type="button">Limpiar imágenes sin usar</button>
        <?php endif; ?></aside>
        <section class="pt-workspace" aria-label="Escena seleccionada">
            <div class="pt-stage">
            <div class="pt-scene-heading"><h2 id="ptTitle">Agrega tu primer espacio</h2><span id="ptStartLabel"></span></div>
            <p id="ptInstructions" class="pt-hint">Selecciona una fotografía para comenzar.</p>
            <div id="ptViewport" class="pt-viewport" tabindex="0" aria-label="Fotografía del espacio; desplaza horizontalmente las panorámicas">
                <div id="ptCanvas" class="pt-canvas"><img id="ptImage" alt="" hidden><div id="ptPoints"></div></div>
                <div id="ptEmpty"><span aria-hidden="true">▧</span><h3>Un recorrido empieza aquí</h3><p>Agrega una fotografía y convierte tus espacios en una experiencia.</p></div>
            </div>
            <div id="ptPanorama" class="pt-panorama" hidden tabindex="0" aria-label="Visor panorámico: arrastra para mirar alrededor y usa más o menos para acercar"></div>
            <button id="ptFlatView" type="button" hidden>Ver fotografía completa</button>
            <div class="pt-connections"><h3>Continúa el recorrido</h3><ul id="ptLinks" class="pt-link-list"></ul></div>
            </div>
            <?php if($editor): ?>
            <aside class="pt-inspector" aria-label="Configuración del espacio"><div class="pt-inspector-title"><span>01</span><h3>El espacio</h3></div>
            <form id="ptSceneForm" class="pt-form" hidden>
                <label>Nombre del espacio<input id="ptName" maxlength="80" required></label>
                <label>Tipo de fotografía<select id="ptType"><option value="imagen">Fotografía normal</option><option value="panorama">Panorámica horizontal</option></select></label>
                <label id="ptCoverageLabel" hidden>Cobertura capturada<select id="ptCoverage"><option value="180">180° · panorámica parcial</option><option value="270">270° · panorámica amplia</option><option value="360">360° · vuelta completa</option></select><span>Elige 360° solo si fotografiaste toda la vuelta. El visor no crea los espacios que no capturaste.</span></label>
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
<script id="ptBoot" type="application/json"><?= json_encode(['editor'=>$editor,'propertyId'=>$propertyId,'data'=>$tourData],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= BASE_URL ?>tour/lib/pannellum.js" defer></script>
<script src="<?= BASE_URL ?>js/recorridos-fotos.js?v=3" defer></script>
<?php if(!$editor): ?></body></html><?php endif; ?>

<?php
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$prop = $bundle['propiedad'] ?? ['id' => 0, 'titulo' => 'Recorrido'];
$tour = $bundle['recorrido'] ?? ['titulo' => 'Recorrido por escenas'];
$isEmbedded = !empty($embedded);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esc($tour['titulo']) ?> | INFONATEC</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>tour/lib/pannellum.css">
    <style>
        :root {
            --gold: #d4af37;
            --bg: #0b111d;
            --panel: rgba(15, 23, 42, 0.9);
            --border: rgba(255, 255, 255, 0.16);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        [hidden] { display: none !important; }
        html, body {
            width: 100%; height: 100%;
            background: var(--bg); color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow: hidden;
        }
        .visor-shell {
            display: flex; flex-direction: column;
            width: 100%; height: 100%;
        }
        .visor-header {
            display: flex; justify-content: space-between; align-items: center;
            gap: 12px; flex-wrap: wrap;
            padding: 12px 18px;
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            z-index: 10;
        }
        .visor-title small { color: var(--gold); font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; display: block; }
        .visor-title h1 { font-size: 16px; font-weight: 700; }
        .visor-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .visor-btn {
            padding: 8px 13px; border-radius: 10px;
            border: 1px solid var(--border); background: rgba(255,255,255,0.08);
            color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .visor-btn-gold { background: var(--gold); color: #111; border-color: var(--gold); }
        .visor-stage {
            position: relative; flex: 1; min-height: 0;
            display: flex; align-items: center; justify-content: center;
            background: #000; overflow: hidden;
        }
        .visor-img-wrap {
            position: relative; display: inline-flex; max-width: 100%; max-height: 100%;
            align-items: center; justify-content: center;
        }
        .visor-img {
            width: auto; height: auto;
            max-width: 100%; max-height: calc(100vh - 180px);
            display: block;
        }
        .visor-panorama { width: 100%; height: 100%; }
        .visor-hotspot {
            position: absolute; transform: translate(-50%, -50%);
            padding: 7px 14px; border-radius: 999px;
            background: rgba(15, 23, 42, 0.92); color: #fff;
            border: 2px solid var(--gold);
            font-size: 13px; font-weight: 700; cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
            transition: transform 0.15s ease;
        }
        .visor-hotspot:hover { transform: translate(-50%, -50%) scale(1.06); background: var(--gold); color: #111; }
        .visor-video-overlay {
            position: absolute; inset: 0; z-index: 20;
            background: rgba(5, 10, 20, 0.94);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 16px; gap: 12px;
        }
        .visor-video-overlay[hidden] { display: none; }
        .visor-video-overlay video {
            width: min(100%, 900px); max-height: 68vh;
            background: #000; border-radius: 12px;
        }
        .visor-footer {
            background: var(--panel); border-top: 1px solid var(--border);
            padding: 10px 16px; display: flex; flex-direction: column; gap: 8px;
        }
        .visor-thumbs {
            display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px;
        }
        .visor-thumb {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 10px 5px 5px; border-radius: 10px;
            border: 1px solid var(--border); background: rgba(255,255,255,0.06);
            color: #cbd5e1; cursor: pointer; flex-shrink: 0; font-size: 12.5px; font-weight: 600;
        }
        .visor-thumb.active { border-color: var(--gold); background: rgba(212, 175, 55, 0.2); color: #fff; }
        .visor-thumb img { width: 48px; height: 34px; object-fit: cover; border-radius: 6px; }
    </style>
</head>
<body>
<div class="visor-shell" id="visorApp">
    <header class="visor-header">
        <div class="visor-title">
            <small><?= !empty($bundle['is_preview']) ? 'Vista previa (Borrador)' : 'Recorrido por escenas' ?></small>
            <h1 id="visorSceneName"><?= $esc($prop['titulo']) ?></h1>
        </div>
        <div class="visor-actions">
            <button type="button" id="btnPlayFragment" class="visor-btn visor-btn-gold" hidden>▶ Ver fragmento de video</button>
            <button type="button" id="btnPrevScene" class="visor-btn" aria-label="Escena anterior">‹ Anterior</button>
            <button type="button" id="btnNextScene" class="visor-btn" aria-label="Escena siguiente">Siguiente ›</button>
            <?php if (!$isEmbedded): ?>
            <a class="visor-btn" href="<?= BASE_URL ?>explorar">Catálogo</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="visor-stage">
        <div id="visorImageWrap" class="visor-img-wrap">
            <img id="visorSceneImg" class="visor-img" src="" alt="">
            <div id="visorHotspots"></div>
        </div>
        <div id="visorPanorama" class="visor-panorama" hidden></div>

        <div id="visorVideoOverlay" class="visor-video-overlay" hidden>
            <div style="display:flex;justify-content:space-between;align-items:center;width:min(100%,900px);gap:10px;flex-wrap:wrap;">
                <strong id="visorVideoLabel">Fragmento del espacio</strong>
                <div style="display:flex;gap:8px;">
                    <button type="button" id="btnReplayFragment" class="visor-btn">↻ Repetir fragmento</button>
                    <button type="button" id="btnCloseFragment" class="visor-btn visor-btn-gold">Volver a la escena</button>
                </div>
            </div>
            <video id="visorFragmentVideo" controls playsinline preload="metadata"></video>
        </div>
    </main>

    <footer class="visor-footer">
        <div id="visorThumbs" class="visor-thumbs" role="list" aria-label="Escenas del inmueble"></div>
    </footer>
</div>

<script src="<?= BASE_URL ?>tour/lib/pannellum.js"></script>
<script src="<?= BASE_URL ?>js/recorrido-contrato.js?v=1.0"></script>
<script>
    window.RECORRIDO_VISOR_INIT = <?= json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>js/recorrido-visor.js?v=1.0"></script>
</body>
</html>

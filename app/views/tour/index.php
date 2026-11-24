<?php
$preApproved = !empty($tourPreApproved);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorrido Virtual 360° — Residencia Minimalista Las Cumbres | INFONATEC</title>

    <!-- Pannellum CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>tour/lib/pannellum.css">

    <style>
        :root {
            --gold: #d4af37;
            --gold-glow: rgba(212, 175, 55, 0.4);
            --dark-bg: rgba(15, 15, 15, 0.88);
            --border-glass: rgba(255, 255, 255, 0.16);
            --white: #ffffff;
            --font-serif: "Georgia", "Playfair Display", serif;
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #0d0d0d;
            font-family: var(--font-sans);
            color: var(--white);
        }

        button:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--gold);
            outline-offset: 2px;
        }

        #panorama {
            width: 100%;
            height: 100%;
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .pnlm-load-button,
        .pnlm-panorama-info {
            display: none !important;
        }

        .pnlm-control {
            background-color: rgba(20, 20, 20, 0.85) !important;
            border-radius: 8px !important;
            border: 1px solid var(--border-glass) !important;
        }

        /* HUD SUPERIOR */
        .tour-header {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            z-index: 15;
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: none;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tour-brand-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            pointer-events: auto;
        }

        .tour-brand {
            background: var(--dark-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        .tour-brand-badge {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 700;
        }

        .tour-brand-title {
            font-family: var(--font-serif);
            font-size: 15px;
            font-weight: 600;
            color: var(--white);
        }

        .tour-room-indicator {
            font-size: 12px;
            color: #cbd5e1;
        }

        .tour-room-indicator span {
            color: var(--white);
            font-weight: 600;
        }

        .tour-switcher {
            background: var(--dark-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tour-switcher a {
            color: #cbd5e1;
            padding: 3px 9px;
            border-radius: 12px;
            text-decoration: none;
        }

        .tour-switcher a.active {
            background: var(--gold);
            color: #111;
            font-weight: 700;
        }

        .tour-actions {
            pointer-events: auto;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .tour-btn {
            background: var(--dark-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            color: var(--white);
            min-width: 40px;
            height: 40px;
            padding: 0 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
            font-size: 13px;
            font-weight: 600;
        }

        .tour-btn:hover,
        .tour-btn.active {
            background: var(--gold);
            color: #111;
            border-color: var(--gold);
        }

        /* CONTROLES ALTERNATIVOS ACCESIBLES (ZOOM + FLECHAS + ESCENAS) */
        .tour-accessible-controls {
            position: absolute;
            right: 16px;
            bottom: 125px;
            z-index: 15;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
            pointer-events: auto;
        }

        .ctrl-card {
            background: var(--dark-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.55);
        }

        .ctrl-dpad {
            display: grid;
            grid-template-columns: repeat(3, 36px);
            grid-template-rows: repeat(3, 36px);
            gap: 4px;
        }

        .ctrl-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #fff;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            min-width: 36px;
            min-height: 36px;
        }

        .ctrl-btn:hover {
            background: var(--gold);
            color: #111;
            border-color: var(--gold);
        }

        .ctrl-zoom-row {
            display: flex;
            gap: 6px;
        }

        .ctrl-zoom-row .ctrl-btn {
            width: 42px;
            height: 38px;
            font-size: 18px;
        }

        /* BARRA INFERIOR */
        .tour-footer {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 15;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            pointer-events: none;
            max-width: 95vw;
        }

        .tour-helper-pill {
            pointer-events: auto;
            background: rgba(10, 10, 10, 0.82);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rooms-bar {
            pointer-events: auto;
            background: var(--dark-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 7px 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            max-width: calc(100vw - 24px);
        }

        .rooms-bar::-webkit-scrollbar {
            display: none;
        }

        .room-tab {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid transparent;
            border-radius: 16px;
            padding: 8px 13px;
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 600;
        }

        .room-tab:hover {
            background: rgba(255, 255, 255, 0.14);
            color: var(--white);
        }

        .room-tab.active {
            background: rgba(212, 175, 55, 0.22);
            border-color: var(--gold);
            color: var(--gold);
        }

        .scene-step-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-glass);
            color: #fff;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            flex: 0 0 auto;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .scene-step-btn:hover {
            background: var(--gold);
            color: #111;
        }

        /* HOTSPOTS */
        .hotspot-nav {
            width: 44px;
            height: 44px;
            margin-left: -22px;
            margin-top: -22px;
            border-radius: 50%;
            background: rgba(20, 20, 20, 0.88);
            border: 2px solid var(--gold);
            box-shadow: 0 0 20px var(--gold-glow), 0 4px 12px rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .hotspot-nav:hover {
            transform: scale(1.15);
            background: var(--gold);
        }

        .hotspot-nav svg {
            width: 20px;
            height: 20px;
            stroke: var(--gold);
            stroke-width: 2.4;
            fill: none;
        }

        .hotspot-nav:hover svg {
            stroke: #111;
        }

        .hotspot-info {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.96);
            border: 2px solid var(--white);
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            font-family: var(--font-serif);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            cursor: pointer;
        }

        .hotspot-info:hover {
            transform: scale(1.18);
            background: var(--gold);
            border-color: var(--gold);
        }

        .hotspot-tooltip {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 15, 15, 0.94);
            border: 1px solid var(--border-glass);
            border-radius: 10px;
            padding: 7px 12px;
            white-space: nowrap;
            color: var(--white);
            font-size: 12px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .hotspot-nav:hover .hotspot-tooltip,
        .hotspot-nav:focus-visible .hotspot-tooltip,
        .hotspot-info:hover .hotspot-tooltip,
        .hotspot-info:focus-visible .hotspot-tooltip {
            opacity: 1;
        }

        /* MODALES E OVERLAYS (INFO, AYUDA, DATA GATE, GALERÍA ESTÁTICA) */
        .overlay-screen {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 60;
            background: rgba(10, 13, 20, 0.94);
            backdrop-filter: blur(12px);
            padding: 24px;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        .overlay-screen.active {
            display: flex;
        }

        .overlay-card {
            background: #141a26;
            border: 1px solid rgba(201, 168, 106, 0.45);
            border-radius: 20px;
            padding: 28px;
            width: 100%;
            max-width: 680px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
            position: relative;
        }

        .overlay-card h2,
        .overlay-card h3 {
            font-family: var(--font-serif);
            color: #fff;
            margin-bottom: 10px;
        }

        .overlay-card p {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.6;
        }

        .gate-badge {
            display: inline-block;
            background: rgba(212, 175, 55, 0.2);
            color: var(--gold);
            border: 1px solid rgba(212, 175, 55, 0.5);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .slow-alert {
            background: rgba(245, 158, 11, 0.16);
            border: 1px solid #f59e0b;
            color: #fde68a;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin: 14px 0;
        }

        .overlay-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-gold-solid {
            background: var(--gold);
            color: #111;
            border: none;
            padding: 12px 20px;
            border-radius: 24px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid var(--border-glass);
            padding: 12px 20px;
            border-radius: 24px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .static-stage {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            height: 360px;
            background: #000;
            margin: 14px 0;
        }

        .static-stage img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .static-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.85);
            color: #fff;
            border: 1px solid var(--border-glass);
            font-size: 22px;
            cursor: pointer;
        }

        .static-nav-prev { left: 12px; }
        .static-nav-next { right: 12px; }

        .static-thumbs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            margin-top: 10px;
        }

        .static-thumb {
            width: 82px;
            height: 54px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid transparent;
            padding: 0;
            background: #000;
            opacity: 0.65;
            cursor: pointer;
            flex: 0 0 auto;
        }

        .static-thumb.active {
            opacity: 1;
            border-color: var(--gold);
        }

        .static-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .info-modal {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 50;
            background: rgba(18, 18, 18, 0.96);
            backdrop-filter: blur(20px);
            border: 1px solid var(--gold);
            border-radius: 18px;
            padding: 24px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
        }

        .info-modal-close {
            position: absolute;
            top: 14px;
            right: 14px;
            background: none;
            border: none;
            color: #cbd5e1;
            font-size: 22px;
            cursor: pointer;
            padding: 4px;
        }

        .info-modal-tag {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--gold);
            font-weight: 700;
            margin-bottom: 6px;
        }

        .info-modal-title {
            font-family: var(--font-serif);
            font-size: 19px;
            color: var(--white);
            margin-bottom: 10px;
        }

        .info-modal-desc {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.6;
        }

        .help-list {
            list-style: none;
            margin-top: 12px;
            display: grid;
            gap: 8px;
            font-size: 13px;
            color: #e2e8f0;
        }

        .help-list kbd {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 2px 7px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }

        .scene-fader {
            position: absolute;
            inset: 0;
            background: #000;
            z-index: 20;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.45s ease-in-out;
        }

        .scene-fader.active {
            opacity: 1;
        }

        @media (max-width: 640px) {
            .tour-accessible-controls {
                right: 10px;
                bottom: 120px;
            }
            .static-stage {
                height: 230px;
            }
        }
    </style>
</head>
<body>

    <div id="panorama" aria-label="Visor panorámico 360 grados"></div>
    <div id="sceneFader" class="scene-fader"></div>

    <!-- HUD Superior -->
    <header class="tour-header">
        <div class="tour-brand-wrap">
            <div class="tour-brand">
                <div>
                    <div class="tour-brand-badge">Tour Virtual 360°</div>
                    <div class="tour-brand-title">Residencia Las Cumbres</div>
                </div>
                <div style="width:1px;height:26px;background:var(--border-glass);"></div>
                <div class="tour-room-indicator">
                    <span>📍 <b id="currentRoomName">Sala Principal</b></span>
                </div>
            </div>

            <div class="tour-switcher">
                <span style="color:#cbd5e1;">Ver:</span>
                <a href="<?= BASE_URL ?>tour" class="active">🏡 Residencia</a>
                <a href="<?= BASE_URL ?>tour/industrial">🏭 Terreno Industrial</a>
            </div>
        </div>

        <div class="tour-actions">
            <button type="button" class="tour-btn" id="btnStaticMode" title="Galería estática accesible (G)" aria-label="Abrir galería estática accesible" onclick="openStaticGallery()">
                🖼️ <span>Galería</span>
            </button>
            <button type="button" class="tour-btn" id="btnHelp" title="Ayuda y atajos de teclado (H)" aria-label="Mostrar ayuda del recorrido 360 grados" onclick="openHelpModal()">
                ?
            </button>
            <button type="button" class="tour-btn" id="btnAutoRotate" title="Giro Automático (R)" aria-label="Activar o pausar giro automático" onclick="toggleAutoRotate()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
            </button>
            <button type="button" class="tour-btn" id="btnFullscreen" title="Pantalla Completa (F)" aria-label="Alternar pantalla completa" onclick="toggleFullscreen()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Controles alternativos accesibles (Flechas + Zoom + Centrar) -->
    <div class="tour-accessible-controls" role="group" aria-label="Controles alternativos de navegación y zoom 360">
        <div class="ctrl-card">
            <div class="ctrl-dpad">
                <span></span>
                <button type="button" class="ctrl-btn" onclick="panView(15, 0)" aria-label="Mirar hacia arriba" title="Mirar arriba (↑)">↑</button>
                <span></span>
                <button type="button" class="ctrl-btn" onclick="panView(0, -25)" aria-label="Girar a la izquierda" title="Girar izquierda (←)">←</button>
                <button type="button" class="ctrl-btn" onclick="resetCameraView()" aria-label="Restablecer vista inicial" title="Centrar vista (0)">⟲</button>
                <button type="button" class="ctrl-btn" onclick="panView(0, 25)" aria-label="Girar a la derecha" title="Girar derecha (→)">→</button>
                <span></span>
                <button type="button" class="ctrl-btn" onclick="panView(-15, 0)" aria-label="Mirar hacia abajo" title="Mirar abajo (↓)">↓</button>
                <span></span>
            </div>
        </div>
        <div class="ctrl-card">
            <div class="ctrl-zoom-row">
                <button type="button" class="ctrl-btn" id="btnZoomIn" onclick="zoomIn()" aria-label="Acercar zoom" title="Acercar (+)">+</button>
                <button type="button" class="ctrl-btn" id="btnZoomOut" onclick="zoomOut()" aria-label="Alejar zoom" title="Alejar (−)">−</button>
            </div>
        </div>
    </div>

    <!-- Footer Flotante con Selector de Salas -->
    <footer class="tour-footer">
        <div class="tour-helper-pill">
            Arrastra o usa flechas ← ↑ ↓ → • Zoom + / − • Pulsa [?] para ayuda o [Galería] para bajo consumo
        </div>

        <nav class="rooms-bar" aria-label="Navegación entre habitaciones">
            <button type="button" class="scene-step-btn" onclick="prevScene()" aria-label="Habitación anterior" title="Habitación anterior">‹</button>
            <button type="button" class="room-tab active" data-scene="sala" onclick="switchScene('sala')">
                <span aria-hidden="true">🛋️</span>
                <span>Sala Principal</span>
            </button>
            <button type="button" class="room-tab" data-scene="comedor" onclick="switchScene('comedor')">
                <span aria-hidden="true">🍽️</span>
                <span>Comedor & Cocina</span>
            </button>
            <button type="button" class="room-tab" data-scene="recamara" onclick="switchScene('recamara')">
                <span aria-hidden="true">🛏️</span>
                <span>Recámara Principal</span>
            </button>
            <button type="button" class="room-tab" data-scene="terraza" onclick="switchScene('terraza')">
                <span aria-hidden="true">🌿</span>
                <span>Terraza & Jardín</span>
            </button>
            <button type="button" class="scene-step-btn" onclick="nextScene()" aria-label="Siguiente habitación" title="Siguiente habitación">›</button>
        </nav>
    </footer>

    <!-- Modal Informativo de Acabados -->
    <div id="infoModal" class="info-modal" role="dialog" aria-modal="true" aria-labelledby="infoTitle">
        <button type="button" class="info-modal-close" onclick="closeInfoModal()" aria-label="Cerrar detalle">&times;</button>
        <div class="info-modal-tag" id="infoTag">DETALLE ARQUITECTÓNICO</div>
        <h3 class="info-modal-title" id="infoTitle">Acabados de Lujo</h3>
        <p class="info-modal-desc" id="infoDesc">Descripción detallada del acabado o especificación de este espacio.</p>
    </div>

    <!-- Modal de Ayuda -->
    <div id="helpModalOverlay" class="overlay-screen" role="dialog" aria-modal="true" aria-labelledby="helpModalTitle" onclick="if(event.target===this) closeHelpModal()">
        <div class="overlay-card" style="max-width:500px;">
            <button type="button" class="info-modal-close" onclick="closeHelpModal()" aria-label="Cerrar ayuda">&times;</button>
            <div class="gate-badge">AYUDA DEL RECORRIDO 360°</div>
            <h2 id="helpModalTitle">Cómo navegar el recorrido</h2>
            <ul class="help-list">
                <li><strong>Ratón o pantalla táctil:</strong> arrastra para girar 360° y usa la rueda para zoom.</li>
                <li><strong>Botones en pantalla:</strong> usa la cruceta direccional (↑ ↓ ← →), centrar (⟲) y los botones <strong>+</strong> / <strong>−</strong> de zoom.</li>
                <li><strong>Teclado:</strong> <kbd>←</kbd> <kbd>↑</kbd> <kbd>↓</kbd> <kbd>→</kbd> mirar alrededor · <kbd>+</kbd> / <kbd>-</kbd> zoom · <kbd>0</kbd> centrar · <kbd>[</kbd> / <kbd>]</kbd> cambiar sala.</li>
                <li><strong>Atajos rápidos:</strong> <kbd>R</kbd> rotación automática · <kbd>F</kbd> pantalla completa · <kbd>G</kbd> galería estática accesible · <kbd>H</kbd> ayuda · <kbd>Esc</kbd> cerrar ventanas.</li>
            </ul>
            <div class="overlay-actions">
                <button type="button" class="btn-gold-solid" onclick="closeHelpModal()">Entendido</button>
            </div>
        </div>
    </div>

    <!-- Gate previo de consumo de datos (acceso directo /tour) -->
    <div id="dataGateOverlay" class="overlay-screen" role="dialog" aria-modal="true" aria-labelledby="dataGateTitle">
        <div class="overlay-card" style="max-width:560px;">
            <span class="gate-badge">Consumo estimado: ~4.6 MB (4 panoramas HD)</span>
            <h2 id="dataGateTitle">Aviso previo de consumo de datos</h2>
            <p>
                El recorrido 360° de Residencia Las Cumbres descarga panoramas interactivos en alta resolución. Puedes iniciarlo ahora o explorar la galería estática accesible de bajo consumo.
            </p>
            <div id="gateSlowAlert" class="slow-alert" style="display:none;">
                ⚠️ Detectamos conexión lenta, modo ahorro de datos o navegador sin conexión. Te recomendamos abrir primero la galería estática accesible.
            </div>
            <div class="overlay-actions">
                <button type="button" class="btn-gold-solid" onclick="approveAndStartTour()">Iniciar recorrido 360°</button>
                <button type="button" class="btn-glass" onclick="openStaticFromGate()">Ver galería estática accesible</button>
            </div>
        </div>
    </div>

    <!-- Galería Estática Accesible Alternativa -->
    <div id="staticGalleryOverlay" class="overlay-screen" role="dialog" aria-modal="true" aria-labelledby="staticTitle">
        <div class="overlay-card" style="max-width:760px;">
            <button type="button" class="info-modal-close" onclick="closeStaticGallery()" aria-label="Cerrar galería estática">&times;</button>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding-right:44px;">
                <span class="gate-badge">GALERÍA ESTÁTICA ACCESIBLE (BAJO CONSUMO)</span>
                <span id="staticCounter" style="font-size:13px;color:#cbd5e1;font-weight:700;">1 / 4</span>
            </div>
            <h2 id="staticTitle">Sala Principal</h2>
            <div class="static-stage">
                <img id="staticImg" src="" alt="">
                <button type="button" class="static-nav-btn static-nav-prev" onclick="prevStatic()" aria-label="Vista anterior">‹</button>
                <button type="button" class="static-nav-btn static-nav-next" onclick="nextStatic()" aria-label="Siguiente vista">›</button>
            </div>
            <p id="staticDesc"></p>
            <div class="static-thumbs" id="staticThumbs"></div>
            <div class="overlay-actions" style="justify-content:space-between;">
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn-glass" onclick="prevStatic()">← Anterior</button>
                    <button type="button" class="btn-glass" onclick="nextStatic()">Siguiente →</button>
                </div>
                <button type="button" class="btn-gold-solid" onclick="switchToInteractiveFromStatic()">Ir al visor 360° interactivo</button>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>tour/lib/pannellum.js"></script>

    <script>
        const BASE_TOUR_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
        const PRE_APPROVED = <?= $preApproved ? 'true' : 'false' ?>;
        const sceneOrder = ['sala', 'comedor', 'recamara', 'terraza'];

        const staticScenes = [
            {
                id: 'sala',
                title: 'Sala Principal',
                image: BASE_TOUR_URL + 'tour/panoramas/sala.jpg',
                alt: 'Vista estática de Sala Principal con doble altura y ventanales térmicos',
                desc: 'Espacio a doble altura con plafonería de iluminación LED indirecta, piso porcelánico gran formato 120×60 cm y ventanales duovent con filtro UV.'
            },
            {
                id: 'comedor',
                title: 'Comedor & Cocina de Diseño',
                image: BASE_TOUR_URL + 'tour/panoramas/comedor.jpg',
                alt: 'Vista estática de Comedor y Cocina integral equipada con isla central de cuarzo',
                desc: 'Cocina integral equipada con barra desayunadora de cuarzo blanco calacatta, parrilla de inducción y campana oculta.'
            },
            {
                id: 'recamara',
                title: 'Recámara Principal',
                image: BASE_TOUR_URL + 'tour/panoramas/recamara.jpg',
                alt: 'Vista estática de Recámara Principal con vista panorámica y vestidor',
                desc: 'Master suite para cama King Size con vestidor walk-in en madera de nogal y baño completo en suite.'
            },
            {
                id: 'terraza',
                title: 'Terraza & Jardín',
                image: BASE_TOUR_URL + 'tour/panoramas/terraza.jpg',
                alt: 'Vista estática de Terraza techada y jardín privado con iluminación cálida',
                desc: 'Área social exterior techada con piso antiderrapante, iluminación cálida empotrada y preparación para asador.'
            }
        ];

        const scenesConfig = {
            default: {
                firstScene: 'sala',
                sceneFadeDuration: 700,
                autoLoad: true,
                showZoomCtrl: false,
                showFullscreenCtrl: false,
                compass: false,
                hfov: 105,
                minHfov: 55,
                maxHfov: 120
            },
            scenes: {
                sala: {
                    title: 'Sala Principal',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/sala.jpg',
                    pitch: 2,
                    yaw: 10,
                    hotSpots: [
                        { pitch: -4, yaw: -55, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'comedor', title: 'Ir al Comedor & Cocina' } },
                        { pitch: -2, yaw: 110, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'terraza', title: 'Salir a la Terraza' } },
                        { pitch: 18, yaw: -15, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'DISEÑO DE INTERIORES', title: 'Doble Altura y Ventanales Térmicos', desc: 'Espacio con plafonería suspendida de iluminación LED perimetral indirecta y ventanales duovent con filtro UV y aislante acústico.' } },
                        { pitch: -24, yaw: 15, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'PISOS Y MATERIALES', title: 'Piso Porcelánico Gran Formato', desc: 'Piso rectificado de 120×60 cm con acabado mate antiderrapante de alta resistencia.' } }
                    ]
                },
                comedor: {
                    title: 'Comedor & Cocina de Diseño',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/comedor.jpg',
                    pitch: -3,
                    yaw: 0,
                    hotSpots: [
                        { pitch: -5, yaw: 130, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'sala', title: 'Volver a la Sala' } },
                        { pitch: -3, yaw: -70, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'recamara', title: 'Ir a Recámara Principal' } },
                        { pitch: -12, yaw: -20, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'EQUIPAMIENTO DE COCINA', title: 'Isla Central con Cubierta de Cuarzo', desc: 'Cocina integral equipada con barra desayunadora de cuarzo blanco calacatta, campana extractora oculta y parrilla de inducción.' } }
                    ]
                },
                recamara: {
                    title: 'Recámara Principal',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/recamara.jpg',
                    pitch: 0,
                    yaw: -30,
                    hotSpots: [
                        { pitch: -6, yaw: 145, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'comedor', title: 'Regresar al Comedor' } },
                        { pitch: 2, yaw: 40, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'terraza', title: 'Acceso a Terraza' } },
                        { pitch: 10, yaw: -95, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'CONFORT & SUITE', title: 'Master Suite con Vista Panorámica', desc: 'Espacio para cama King Size con vestidor independiente (walk-in closet) de madera de nogal y baño completo en suite.' } }
                    ]
                },
                terraza: {
                    title: 'Terraza & Jardín',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/terraza.jpg',
                    pitch: 2,
                    yaw: 15,
                    hotSpots: [
                        { pitch: -2, yaw: -120, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'sala', title: 'Entrar a la Sala' } },
                        { pitch: 0, yaw: -35, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'recamara', title: 'Ir a Recámara' } },
                        { pitch: -18, yaw: 45, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'ÁREAS VERDES Y SOCIALES', title: 'Terraza Techada y Jardín Privado', desc: 'Área social exterior con piso cerámico imitación madera, iluminación cálida empotrada y preparación para asador.' } }
                    ]
                }
            }
        };

        let viewer = null;
        let isAutoRotating = false;
        let currentSceneId = 'sala';
        let staticIdx = 0;

        function isSlowNet() {
            if (!navigator.onLine) return true;
            const c = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            return !!(c && (c.saveData || c.effectiveType === '2g' || c.effectiveType === 'slow-2g' || c.effectiveType === '3g'));
        }

        function initViewer() {
            if (viewer) return;
            viewer = pannellum.viewer('panorama', scenesConfig);
            viewer.on('load', function () {
                currentSceneId = viewer.getScene();
                updateHudState(currentSceneId);
            });
            const stopAuto = function () {
                if (isAutoRotating) {
                    viewer.stopAutoRotate();
                    document.getElementById('btnAutoRotate').classList.remove('active');
                    isAutoRotating = false;
                }
            };
            viewer.on('mousedown', stopAuto);
            viewer.on('touchstart', stopAuto);
        }

        function createNavHotspot(hotSpotDiv, args) {
            hotSpotDiv.classList.add('hotspot-nav');
            hotSpotDiv.setAttribute('role', 'button');
            hotSpotDiv.setAttribute('tabindex', '0');
            hotSpotDiv.setAttribute('aria-label', args.title);
            hotSpotDiv.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg><div class="hotspot-tooltip">' + args.title + '</div>';
            const trigger = function (e) {
                e.stopPropagation();
                switchScene(args.targetScene);
            };
            hotSpotDiv.onclick = trigger;
            hotSpotDiv.onkeydown = function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    trigger(e);
                }
            };
        }

        function createInfoHotspot(hotSpotDiv, args) {
            hotSpotDiv.classList.add('hotspot-info');
            hotSpotDiv.setAttribute('role', 'button');
            hotSpotDiv.setAttribute('tabindex', '0');
            hotSpotDiv.setAttribute('aria-label', args.title);
            hotSpotDiv.innerHTML = '<span aria-hidden="true">i</span><div class="hotspot-tooltip">' + args.title + '</div>';
            const trigger = function (e) {
                e.stopPropagation();
                openInfoModal(args.tag, args.title, args.desc);
            };
            hotSpotDiv.onclick = trigger;
            hotSpotDiv.onkeydown = function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    trigger(e);
                }
            };
        }

        function switchScene(sceneId) {
            if (!scenesConfig.scenes[sceneId]) return;
            if (!viewer) initViewer();
            currentSceneId = sceneId;
            const fader = document.getElementById('sceneFader');
            fader.classList.add('active');
            setTimeout(function () {
                viewer.loadScene(sceneId);
                updateHudState(sceneId);
                setTimeout(function () { fader.classList.remove('active'); }, 280);
            }, 220);
        }

        function prevScene() {
            const idx = sceneOrder.indexOf(currentSceneId);
            const nextIdx = (idx - 1 + sceneOrder.length) % sceneOrder.length;
            switchScene(sceneOrder[nextIdx]);
        }

        function nextScene() {
            const idx = sceneOrder.indexOf(currentSceneId);
            const nextIdx = (idx + 1) % sceneOrder.length;
            switchScene(sceneOrder[nextIdx]);
        }

        function updateHudState(sceneId) {
            const scene = scenesConfig.scenes[sceneId];
            if (!scene) return;
            const roomNameEl = document.getElementById('currentRoomName');
            if (roomNameEl) roomNameEl.textContent = scene.title;
            document.querySelectorAll('.room-tab').forEach(function (tab) {
                tab.classList.toggle('active', tab.getAttribute('data-scene') === sceneId);
            });
        }

        function panView(dPitch, dYaw) {
            if (!viewer) return;
            viewer.setPitch(Math.max(-85, Math.min(85, viewer.getPitch() + dPitch)));
            viewer.setYaw(viewer.getYaw() + dYaw);
        }

        function resetCameraView() {
            if (!viewer) return;
            const scene = scenesConfig.scenes[currentSceneId] || scenesConfig.scenes.sala;
            viewer.setPitch(scene.pitch || 0);
            viewer.setYaw(scene.yaw || 0);
            viewer.setHfov(scenesConfig.default.hfov);
        }

        function zoomIn() {
            if (!viewer) return;
            viewer.setHfov(Math.max(scenesConfig.default.minHfov, viewer.getHfov() - 12));
        }

        function zoomOut() {
            if (!viewer) return;
            viewer.setHfov(Math.min(scenesConfig.default.maxHfov, viewer.getHfov() + 12));
        }

        function toggleAutoRotate() {
            if (!viewer) initViewer();
            isAutoRotating = !isAutoRotating;
            const btn = document.getElementById('btnAutoRotate');
            if (isAutoRotating) {
                viewer.startAutoRotate(-2);
                btn.classList.add('active');
            } else {
                viewer.stopAutoRotate();
                btn.classList.remove('active');
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function () {});
            } else if (document.exitFullscreen) {
                document.exitFullscreen().catch(function () {});
            }
        }

        function openInfoModal(tag, title, desc) {
            document.getElementById('infoTag').textContent = tag;
            document.getElementById('infoTitle').textContent = title;
            document.getElementById('infoDesc').textContent = desc;
            document.getElementById('infoModal').style.display = 'block';
        }

        function closeInfoModal() {
            document.getElementById('infoModal').style.display = 'none';
        }

        function openHelpModal() {
            document.getElementById('helpModalOverlay').classList.add('active');
        }

        function closeHelpModal() {
            document.getElementById('helpModalOverlay').classList.remove('active');
        }

        function approveAndStartTour() {
            sessionStorage.setItem('infonatec_360_approved', '1');
            document.getElementById('dataGateOverlay').classList.remove('active');
            initViewer();
        }

        function openStaticFromGate() {
            document.getElementById('dataGateOverlay').classList.remove('active');
            openStaticGallery();
        }

        function openStaticGallery() {
            const idx = sceneOrder.indexOf(currentSceneId);
            staticIdx = idx >= 0 ? idx : 0;
            renderStaticThumbs();
            renderStaticSlide(staticIdx);
            document.getElementById('staticGalleryOverlay').classList.add('active');
        }

        function closeStaticGallery() {
            document.getElementById('staticGalleryOverlay').classList.remove('active');
        }

        function switchToInteractiveFromStatic() {
            closeStaticGallery();
            approveAndStartTour();
        }

        function renderStaticThumbs() {
            const wrap = document.getElementById('staticThumbs');
            wrap.innerHTML = '';
            staticScenes.forEach(function (s, idx) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'static-thumb' + (idx === staticIdx ? ' active' : '');
                btn.setAttribute('aria-label', 'Ver ' + s.title);
                btn.innerHTML = '<img src="' + s.image + '" alt="' + s.alt + '">';
                btn.onclick = function () { renderStaticSlide(idx); };
                wrap.appendChild(btn);
            });
        }

        function renderStaticSlide(idx) {
            const total = staticScenes.length;
            staticIdx = ((idx % total) + total) % total;
            const item = staticScenes[staticIdx];
            const img = document.getElementById('staticImg');
            img.src = item.image;
            img.alt = item.alt;
            document.getElementById('staticTitle').textContent = item.title;
            document.getElementById('staticDesc').textContent = item.desc;
            document.getElementById('staticCounter').textContent = (staticIdx + 1) + ' / ' + total;
            document.querySelectorAll('#staticThumbs .static-thumb').forEach(function (t, i) {
                t.classList.toggle('active', i === staticIdx);
            });
        }

        function prevStatic() { renderStaticSlide(staticIdx - 1); }
        function nextStatic() { renderStaticSlide(staticIdx + 1); }

        document.getElementById('panorama').addEventListener('click', closeInfoModal);

        window.addEventListener('keydown', function (e) {
            const staticOpen = document.getElementById('staticGalleryOverlay').classList.contains('active');
            const helpOpen = document.getElementById('helpModalOverlay').classList.contains('active');

            if (e.key === 'Escape') {
                closeInfoModal();
                closeHelpModal();
                closeStaticGallery();
                return;
            }

            if (staticOpen) {
                if (e.key === 'ArrowLeft') { e.preventDefault(); prevStatic(); }
                if (e.key === 'ArrowRight') { e.preventDefault(); nextStatic(); }
                return;
            }

            if (helpOpen) return;

            if (e.key === 'ArrowLeft') { e.preventDefault(); panView(0, -25); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); panView(0, 25); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); panView(15, 0); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); panView(-15, 0); }
            else if (e.key === '+' || e.key === '=') { e.preventDefault(); zoomIn(); }
            else if (e.key === '-' || e.key === '_') { e.preventDefault(); zoomOut(); }
            else if (e.key === '0') { e.preventDefault(); resetCameraView(); }
            else if (e.key === '[') { e.preventDefault(); prevScene(); }
            else if (e.key === ']') { e.preventDefault(); nextScene(); }
            else if (e.key === 'r' || e.key === 'R') { toggleAutoRotate(); }
            else if (e.key === 'f' || e.key === 'F') { toggleFullscreen(); }
            else if (e.key === 'g' || e.key === 'G') { openStaticGallery(); }
            else if (e.key === 'h' || e.key === 'H' || e.key === '?') { openHelpModal(); }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const approved = PRE_APPROVED || (sessionStorage.getItem('infonatec_360_approved') === '1' && !isSlowNet());
            if (approved) {
                initViewer();
            } else {
                const slowAlert = document.getElementById('gateSlowAlert');
                if (slowAlert) slowAlert.style.display = isSlowNet() ? 'block' : 'none';
                document.getElementById('dataGateOverlay').classList.add('active');
            }
        });
    </script>
</body>
</html>

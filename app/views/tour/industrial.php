<?php
$preApproved = !empty($tourPreApproved);
$csrfToken = Security::csrfToken();
$tourPropertyId = !empty($selectedProperty['id']) ? (int) $selectedProperty['id'] : 6;
$tourTitle = !empty($selectedProperty['title']) ? $selectedProperty['title'] : 'Terreno & Parque Industrial';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorrido Virtual 360° — <?= htmlspecialchars($tourTitle, ENT_QUOTES, 'UTF-8') ?> | INFONATEC</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>tour/lib/pannellum.css">

    <style>
        :root {
            --primary: #c9a86a;
            --primary-glow: rgba(201, 168, 106, 0.45);
            --industrial-blue: #0284c7;
            --industrial-glow: rgba(2, 132, 199, 0.4);
            --dark-bg: rgba(15, 23, 42, 0.9);
            --dark-card: rgba(15, 23, 42, 0.96);
            --border-glass: rgba(255, 255, 255, 0.16);
            --white: #ffffff;
            --text-muted: #cbd5e1;
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-mono: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
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
            background-color: #0b0f17;
            font-family: var(--font-sans);
            color: var(--white);
        }

        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        textarea:focus-visible {
            outline: 2px solid var(--primary);
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

        .tour-header {
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            z-index: 20;
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: none;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tour-brand-container {
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tour-brand {
            background: var(--dark-bg);
            backdrop-filter: blur(18px);
            border: 1px solid var(--border-glass);
            border-radius: 30px;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.55);
        }

        .tour-brand-badge {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--primary);
            font-weight: 800;
        }

        .tour-brand-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--white);
        }

        .tour-zone-indicator {
            font-size: 12px;
            color: var(--text-muted);
        }

        .tour-zone-indicator b {
            color: #38bdf8;
        }

        .property-switcher {
            background: var(--dark-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 6px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .property-switcher a {
            color: #e2e8f0;
            text-decoration: none;
            padding: 3px 9px;
            border-radius: 12px;
        }

        .property-switcher a.active {
            background: var(--primary);
            color: #0f172a;
            font-weight: 700;
        }

        .tour-actions {
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tour-btn {
            background: var(--dark-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            color: var(--white);
            height: 40px;
            padding: 0 13px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
            font-size: 13px;
            font-weight: 600;
        }

        .tour-btn-icon-only {
            width: 40px;
            padding: 0;
            border-radius: 50%;
        }

        .tour-btn:hover,
        .tour-btn.active {
            background: var(--primary);
            color: #0f172a;
            border-color: var(--primary);
        }

        /* CONTROLES ALTERNATIVOS ACCESIBLES */
        .tour-accessible-controls {
            position: absolute;
            right: 16px;
            bottom: 125px;
            z-index: 20;
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
            min-width: 36px;
            min-height: 36px;
        }

        .ctrl-btn:hover {
            background: var(--primary);
            color: #0f172a;
            border-color: var(--primary);
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
            z-index: 20;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            pointer-events: none;
            max-width: 96vw;
        }

        .tour-helper-pill {
            pointer-events: auto;
            background: var(--dark-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            color: #cbd5e1;
        }

        .zones-bar {
            pointer-events: auto;
            background: var(--dark-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 26px;
            padding: 6px 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.65);
            max-width: calc(100vw - 24px);
        }

        .zones-bar::-webkit-scrollbar {
            display: none;
        }

        .zone-tab {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid transparent;
            border-radius: 20px;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            white-space: nowrap;
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 600;
        }

        .zone-tab:hover {
            background: rgba(255, 255, 255, 0.14);
            color: var(--white);
        }

        .zone-tab.active {
            background: rgba(201, 168, 106, 0.22);
            border-color: var(--primary);
            color: var(--primary);
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
            background: var(--primary);
            color: #0f172a;
        }

        /* HOTSPOTS */
        .hotspot-nav {
            width: 46px;
            height: 46px;
            margin-left: -23px;
            margin-top: -23px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid var(--primary);
            box-shadow: 0 0 22px var(--primary-glow), 0 4px 14px rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .hotspot-nav:hover {
            transform: scale(1.18);
            background: var(--primary);
        }

        .hotspot-nav svg {
            width: 22px;
            height: 22px;
            stroke: var(--primary);
            stroke-width: 2.4;
            fill: none;
        }

        .hotspot-nav:hover svg {
            stroke: #0f172a;
        }

        .hotspot-info {
            width: 40px;
            height: 40px;
            margin-left: -20px;
            margin-top: -20px;
            border-radius: 50%;
            background: rgba(2, 132, 199, 0.94);
            border: 2px solid #38bdf8;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 0 20px var(--industrial-glow);
            cursor: pointer;
        }

        .hotspot-info:hover {
            transform: scale(1.2);
            background: #38bdf8;
            color: #0f172a;
        }

        .hotspot-tooltip {
            position: absolute;
            bottom: 52px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid var(--border-glass);
            border-radius: 10px;
            padding: 7px 12px;
            white-space: nowrap;
            color: var(--white);
            font-size: 12px;
            font-weight: 600;
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

        /* MODALES Y OVERLAYS */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.82);
            backdrop-filter: blur(10px);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-overlay.active {
            display: flex;
        }

        .info-modal {
            background: var(--dark-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 28px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
            position: relative;
        }

        .modal-close-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #e2e8f0;
            font-size: 20px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-badge-tag {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #38bdf8;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .info-modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 12px;
        }

        .info-modal-desc {
            font-size: 14px;
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .info-metric-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .info-metric-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 10px 14px;
        }

        .info-metric-label {
            font-size: 11px;
            color: #cbd5e1;
            margin-bottom: 4px;
        }

        .info-metric-value {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            font-family: var(--font-mono);
        }

        .ficha-modal {
            max-width: 700px;
            max-height: 88vh;
            overflow-y: auto;
        }

        .ficha-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 13px;
        }

        .ficha-table th,
        .ficha-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ficha-table th {
            color: #cbd5e1;
            width: 38%;
        }

        .ficha-table td {
            color: #f8fafc;
        }

        /* MINIMAPA */
        .minimap-card {
            position: absolute;
            top: 76px;
            right: 16px;
            z-index: 20;
            background: var(--dark-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 12px;
            width: 220px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
            display: none;
        }

        .minimap-card.active {
            display: block;
        }

        .minimap-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .minimap-svg {
            width: 100%;
            height: 140px;
            background: #0a0f18;
            border-radius: 10px;
        }

        .minimap-pin {
            cursor: pointer;
        }

        .scene-fader {
            position: absolute;
            inset: 0;
            background: #000;
            z-index: 30;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.4s ease-in-out;
        }

        .scene-fader.active {
            opacity: 1;
        }

        /* DATA GATE + GALERÍA ESTÁTICA + AYUDA + CONTACTO */
        .slow-alert {
            background: rgba(245, 158, 11, 0.16);
            border: 1px solid #f59e0b;
            color: #fde68a;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .static-stage {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            height: 340px;
            background: #000;
            margin: 12px 0;
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
            border-color: var(--primary);
        }

        .static-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .help-list {
            list-style: none;
            display: grid;
            gap: 8px;
            font-size: 13px;
            color: #e2e8f0;
            margin-bottom: 18px;
        }

        .help-list kbd {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 2px 7px;
            border-radius: 5px;
            font-family: monospace;
        }

        .contact-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 12px;
        }

        .contact-field label {
            font-size: 12px;
            color: #cbd5e1;
            font-weight: 600;
        }

        .contact-field input,
        .contact-field textarea {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .tour-accessible-controls {
                right: 10px;
                bottom: 120px;
            }
            .minimap-card {
                top: auto;
                bottom: 130px;
                left: 10px;
                right: auto;
                width: 185px;
            }
            .static-stage {
                height: 220px;
            }
        }
    </style>
</head>
<body>

    <div id="panorama" aria-label="Visor panorámico industrial 360 grados"></div>
    <div id="sceneFader" class="scene-fader"></div>

    <!-- Header Flotante -->
    <header class="tour-header">
        <div class="tour-brand-container">
            <div class="tour-brand">
                <div>
                    <div class="tour-brand-badge">Tour Industrial 360°</div>
                    <div class="tour-brand-title"><?= htmlspecialchars($tourTitle, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div style="width:1px;height:26px;background:var(--border-glass);"></div>
                <div class="tour-zone-indicator">
                    <span>📍 <b id="currentZoneName">Vista Aérea Master Plan</b></span>
                </div>
            </div>

            <div class="property-switcher">
                <span>Ver:</span>
                <a href="<?= BASE_URL ?>tour/industrial" class="active">🏭 Terreno Industrial</a>
                <a href="<?= BASE_URL ?>tour">🏡 Residencia Las Cumbres</a>
            </div>
        </div>

        <div class="tour-actions">
            <button type="button" class="tour-btn" id="btnFichaTecnica" onclick="openFichaModal()" title="Ficha Técnica Industrial">
                📄 <span>Ficha Técnica</span>
            </button>
            <button type="button" class="tour-btn" id="btnStaticMode" onclick="openStaticGallery()" title="Galería estática accesible (G)" aria-label="Abrir galería estática accesible">
                🖼️ <span>Galería</span>
            </button>
            <button type="button" class="tour-btn tour-btn-icon-only" id="btnHelp" onclick="openHelpModal()" title="Ayuda y atajos (H)" aria-label="Mostrar ayuda del recorrido 360">
                ?
            </button>
            <button type="button" class="tour-btn tour-btn-icon-only" id="btnMinimap" title="Master Plan / Mapa (M)" aria-label="Mostrar u ocultar Master Plan" onclick="toggleMinimap()">
                🗺️
            </button>
            <button type="button" class="tour-btn tour-btn-icon-only" id="btnAutoRotate" title="Giro Automático (R)" aria-label="Activar o pausar giro automático" onclick="toggleAutoRotate()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
            </button>
            <button type="button" class="tour-btn tour-btn-icon-only" id="btnFullscreen" title="Pantalla Completa (F)" aria-label="Alternar pantalla completa" onclick="toggleFullscreen()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Controles alternativos accesibles -->
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

    <!-- Minimapa Interactivo / Master Plan -->
    <div class="minimap-card" id="minimapCard" role="region" aria-label="Minimapa del parque industrial">
        <div class="minimap-header">
            <span>🗺️ Master Plan</span>
            <button type="button" onclick="toggleMinimap()" aria-label="Cerrar minimapa" style="background:none;border:none;color:#cbd5e1;cursor:pointer;">✕</button>
        </div>
        <svg class="minimap-svg" viewBox="0 0 200 140">
            <rect x="15" y="15" width="75" height="45" fill="#1e293b" rx="4" stroke="#334155" stroke-width="1.5"/>
            <rect x="105" y="15" width="80" height="45" fill="#1e293b" rx="4" stroke="#334155" stroke-width="1.5"/>
            <rect x="15" y="75" width="75" height="50" fill="#1e293b" rx="4" stroke="#334155" stroke-width="1.5"/>
            <rect x="105" y="75" width="80" height="50" fill="#1e293b" rx="4" stroke="#334155" stroke-width="1.5"/>
            <line x1="0" y1="67" x2="200" y2="67" stroke="#475569" stroke-width="8"/>
            <line x1="97" y1="0" x2="97" y2="140" stroke="#475569" stroke-width="8"/>
            <rect x="18" y="78" width="69" height="44" fill="rgba(201, 168, 106, 0.25)" stroke="#c9a86a" stroke-width="2" stroke-dasharray="3,2"/>
            <text x="52" y="103" fill="#c9a86a" font-size="8" font-weight="bold" text-anchor="middle">LOTE EN VENTA</text>
            <circle class="minimap-pin" id="mapPinAerea" cx="100" cy="35" r="5" fill="#38bdf8" onclick="switchScene('aerea')"/>
            <circle class="minimap-pin" id="mapPinAcceso" cx="97" cy="130" r="5" fill="#22c55e" onclick="switchScene('acceso')"/>
            <circle class="minimap-pin" id="mapPinLote" cx="52" cy="100" r="6" fill="#c9a86a" onclick="switchScene('lote')"/>
            <circle class="minimap-pin" id="mapPinSubestacion" cx="145" cy="38" r="5" fill="#eab308" onclick="switchScene('subestacion')"/>
            <circle class="minimap-pin" id="mapPinManiobras" cx="145" cy="100" r="5" fill="#f97316" onclick="switchScene('maniobras')"/>
        </svg>
        <div style="font-size:10px;color:#cbd5e1;margin-top:6px;text-align:center;">
            Toca un punto para ir a esa zona
        </div>
    </div>

    <!-- Barra Inferior de Zonas -->
    <footer class="tour-footer">
        <div class="tour-helper-pill">
            Arrastra o usa flechas ← ↑ ↓ → • Zoom + / − • Pulsa [?] para ayuda o [Galería] para bajo consumo
        </div>

        <nav class="zones-bar" aria-label="Navegación entre zonas industriales">
            <button type="button" class="scene-step-btn" onclick="prevScene()" aria-label="Zona anterior" title="Zona anterior">‹</button>
            <button type="button" class="zone-tab active" data-scene="aerea" onclick="switchScene('aerea')">
                <span aria-hidden="true">🛰️</span>
                <span>Vista Aérea</span>
            </button>
            <button type="button" class="zone-tab" data-scene="acceso" onclick="switchScene('acceso')">
                <span aria-hidden="true">🚛</span>
                <span>Acceso & Caseta</span>
            </button>
            <button type="button" class="zone-tab" data-scene="lote" onclick="switchScene('lote')">
                <span aria-hidden="true">📐</span>
                <span>Plataforma Nivelada</span>
            </button>
            <button type="button" class="zone-tab" data-scene="subestacion" onclick="switchScene('subestacion')">
                <span aria-hidden="true">⚡</span>
                <span>Subestación 115 kV</span>
            </button>
            <button type="button" class="zone-tab" data-scene="maniobras" onclick="switchScene('maniobras')">
                <span aria-hidden="true">📦</span>
                <span>Patio Logístico</span>
            </button>
            <button type="button" class="scene-step-btn" onclick="nextScene()" aria-label="Siguiente zona" title="Siguiente zona">›</button>
        </nav>
    </footer>

    <!-- Modal Info Técnica -->
    <div class="modal-overlay" id="infoModalOverlay" onclick="if(event.target===this) closeInfoModal()">
        <div class="info-modal" role="dialog" aria-modal="true" aria-labelledby="infoTitle">
            <button type="button" class="modal-close-btn" onclick="closeInfoModal()" aria-label="Cerrar especificación">&times;</button>
            <div class="info-badge-tag" id="infoTag">INFRAESTRUCTURA INDUSTRIAL</div>
            <h3 class="info-modal-title" id="infoTitle">Título del Componente</h3>
            <p class="info-modal-desc" id="infoDesc">Descripción técnica del área.</p>
            <div class="info-metric-grid">
                <div class="info-metric-card">
                    <div class="info-metric-label" id="metricLabel1">Capacidad</div>
                    <div class="info-metric-value" id="metricVal1">6.5 ton/m²</div>
                </div>
                <div class="info-metric-card">
                    <div class="info-metric-label" id="metricLabel2">Normativa</div>
                    <div class="info-metric-value" id="metricVal2">I-3 Pesada</div>
                </div>
            </div>
            <button type="button" class="tour-btn" onclick="openFichaModal(); closeInfoModal();" style="width:100%;border-radius:12px;background:var(--primary);color:#0f172a;justify-content:center;">
                Ver Ficha Técnica Completa →
            </button>
        </div>
    </div>

    <!-- Modal Ficha Técnica Completa -->
    <div class="modal-overlay" id="fichaModalOverlay" onclick="if(event.target===this) closeFichaModal()">
        <div class="info-modal ficha-modal" role="dialog" aria-modal="true" aria-labelledby="fichaTitle">
            <button type="button" class="modal-close-btn" onclick="closeFichaModal()" aria-label="Cerrar ficha técnica">&times;</button>
            <div class="info-badge-tag" style="color:var(--primary);">DOCUMENTO DE INGENIERÍA & FACTIBILIDAD</div>
            <h3 class="info-modal-title" id="fichaTitle">Ficha Técnica — <?= htmlspecialchars($tourTitle, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="info-modal-desc">
                Especificaciones técnicas, capacidad de servicios y marco normativo para naves industriales, manufactura o centros de distribución (CEDIS).
            </p>
            <table class="ficha-table">
                <tbody>
                    <tr><th>Superficie Total</th><td><strong>12,500 m²</strong> (Módulos divisibles desde 1,200 / 5,000 m²)</td></tr>
                    <tr><th>Uso de Suelo</th><td>Industrial I-3 (Manufactura Pesada, Logística y Almacenaje)</td></tr>
                    <tr><th>Capacidad de Carga</th><td><strong>6.5 a 8.0 ton/m²</strong> · Compactación 95% Proctor Modificado</td></tr>
                    <tr><th>Energía Eléctrica</th><td>Media y Alta Tensión (115 kV / 13.8 kV) · Factibilidad hasta 20 MVA</td></tr>
                    <tr><th>Gas Natural</th><td>Ducto industrial de 4" a 4 kg/cm² a pie de lote</td></tr>
                    <tr><th>Contra Incendio</th><td>Cisterna 1.2M L bajo norma NFPA 20 / 25</td></tr>
                    <tr><th>Vialidades</th><td>Concreto hidráulico MR-45 espesor 22 cm (Tráileres 53' Full)</td></tr>
                    <tr><th>Seguridad</th><td>Caseta blindada 24/7, control LPR CCTV y báscula de 80 ton</td></tr>
                    <tr><th>Situación Legal</th><td>Escritura pública inmediata, libre de gravamen, permisos MIA vigentes</td></tr>
                </tbody>
            </table>
            <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="tour-btn" onclick="contactAgent()" style="flex:1;min-width:200px;border-radius:12px;background:var(--primary);color:#0f172a;justify-content:center;">
                    📞 Solicitar Cotización & Visita
                </button>
                <button type="button" class="tour-btn" onclick="closeFichaModal()" style="border-radius:12px;">
                    Cerrar Ficha
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Ayuda -->
    <div class="modal-overlay" id="helpModalOverlay" onclick="if(event.target===this) closeHelpModal()">
        <div class="info-modal" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
            <button type="button" class="modal-close-btn" onclick="closeHelpModal()" aria-label="Cerrar ayuda">&times;</button>
            <div class="info-badge-tag" style="color:var(--primary);">AYUDA DEL RECORRIDO 360°</div>
            <h3 class="info-modal-title" id="helpTitle">Controles y atajos accesibles</h3>
            <ul class="help-list">
                <li><strong>Ratón o táctil:</strong> arrastra para rotar 360° y usa la rueda para zoom.</li>
                <li><strong>Controles en pantalla:</strong> cruceta direccional (↑ ↓ ← →), centrar (⟲) y botones <strong>+</strong> / <strong>−</strong> de zoom.</li>
                <li><strong>Teclado:</strong> <kbd>←</kbd> <kbd>↑</kbd> <kbd>↓</kbd> <kbd>→</kbd> mover vista · <kbd>+</kbd> / <kbd>-</kbd> zoom · <kbd>0</kbd> centrar · <kbd>[</kbd> / <kbd>]</kbd> cambiar zona.</li>
                <li><strong>Atajos:</strong> <kbd>M</kbd> minimapa · <kbd>R</kbd> rotación automática · <kbd>F</kbd> pantalla completa · <kbd>G</kbd> galería estática · <kbd>H</kbd> ayuda.</li>
            </ul>
            <button type="button" class="tour-btn" onclick="closeHelpModal()" style="width:100%;border-radius:12px;background:var(--primary);color:#0f172a;justify-content:center;">
                Entendido
            </button>
        </div>
    </div>

    <!-- Gate de consumo de datos -->
    <div class="modal-overlay" id="dataGateOverlay">
        <div class="info-modal" role="dialog" aria-modal="true" aria-labelledby="gateTitle" style="max-width:560px;">
            <div class="info-badge-tag" style="color:var(--primary);">CONSUMO ESTIMADO: ~6.4 MB (5 ZONAS HD)</div>
            <h3 class="info-modal-title" id="gateTitle">Aviso previo de consumo de datos</h3>
            <p class="info-modal-desc">
                El recorrido 360° industrial descarga 5 panoramas de alta resolución. Puedes iniciar la experiencia 360° ahora o abrir la galería estática accesible de bajo consumo.
            </p>
            <div id="gateSlowAlert" class="slow-alert" style="display:none;">
                ⚠️ Detectamos conexión lenta, ahorro de datos o navegador sin conexión. Te recomendamos abrir primero la galería estática accesible.
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="tour-btn" onclick="approveAndStartTour()" style="flex:1;min-width:190px;border-radius:12px;background:var(--primary);color:#0f172a;justify-content:center;">
                    Iniciar recorrido 360°
                </button>
                <button type="button" class="tour-btn" onclick="openStaticFromGate()" style="flex:1;min-width:190px;border-radius:12px;justify-content:center;">
                    Ver galería estática accesible
                </button>
            </div>
        </div>
    </div>

    <!-- Galería Estática Accesible Alternativa -->
    <div class="modal-overlay" id="staticGalleryOverlay" onclick="if(event.target===this) closeStaticGallery()">
        <div class="info-modal" role="dialog" aria-modal="true" aria-labelledby="staticTitle" style="max-width:740px;">
            <button type="button" class="modal-close-btn" onclick="closeStaticGallery()" aria-label="Cerrar galería estática">&times;</button>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding-right:44px;">
                <span class="info-badge-tag" style="color:var(--primary);margin-bottom:0;">GALERÍA ESTÁTICA ACCESIBLE (BAJO CONSUMO)</span>
                <span id="staticCounter" style="font-size:13px;color:#cbd5e1;font-weight:700;">1 / 5</span>
            </div>
            <h3 class="info-modal-title" id="staticTitle" style="margin-top:8px;">Vista Aérea</h3>
            <div class="static-stage">
                <img id="staticImg" src="" alt="">
                <button type="button" class="static-nav-btn static-nav-prev" onclick="prevStatic()" aria-label="Vista anterior">‹</button>
                <button type="button" class="static-nav-btn static-nav-next" onclick="nextStatic()" aria-label="Siguiente vista">›</button>
            </div>
            <p class="info-modal-desc" id="staticDesc" style="margin-bottom:10px;"></p>
            <div class="static-thumbs" id="staticThumbs"></div>
            <div style="margin-top:16px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <div style="display:flex;gap:8px;">
                    <button type="button" class="tour-btn" onclick="prevStatic()">← Anterior</button>
                    <button type="button" class="tour-btn" onclick="nextStatic()">Siguiente →</button>
                </div>
                <button type="button" class="tour-btn" onclick="switchToInteractiveFromStatic()" style="background:var(--primary);color:#0f172a;">
                    Ir al visor 360° interactivo
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de contacto directo cuando se abre en pestaña independiente -->
    <div class="modal-overlay" id="contactModalOverlay" onclick="if(event.target===this) closeContactModal()">
        <div class="info-modal" role="dialog" aria-modal="true" aria-labelledby="contactModalTitle">
            <button type="button" class="modal-close-btn" onclick="closeContactModal()" aria-label="Cerrar formulario">&times;</button>
            <div class="info-badge-tag" style="color:var(--primary);">SOLICITUD DE COTIZACIÓN Y VISITA</div>
            <h3 class="info-modal-title" id="contactModalTitle"><?= htmlspecialchars($tourTitle, ENT_QUOTES, 'UTF-8') ?></h3>
            <form onsubmit="submitStandaloneContact(event)">
                <div class="contact-field">
                    <label for="indContactName">Nombre completo</label>
                    <input type="text" id="indContactName" required>
                </div>
                <div class="contact-field">
                    <label for="indContactPhone">Teléfono</label>
                    <input type="tel" id="indContactPhone" required>
                </div>
                <div class="contact-field">
                    <label for="indContactEmail">Correo electrónico</label>
                    <input type="email" id="indContactEmail" required>
                </div>
                <div class="contact-field">
                    <label for="indContactMsg">Mensaje</label>
                    <textarea id="indContactMsg" rows="3">Solicito cotización y visita técnica para el terreno industrial.</textarea>
                </div>
                <div id="indContactStatus" style="font-size:13px;margin-bottom:10px;color:#38bdf8;"></div>
                <button type="submit" class="tour-btn" style="width:100%;border-radius:12px;background:var(--primary);color:#0f172a;justify-content:center;">
                    Enviar solicitud
                </button>
            </form>
        </div>
    </div>

    <script src="<?= BASE_URL ?>tour/lib/pannellum.js"></script>

    <script>
        const BASE_TOUR_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
        const CSRF_TOKEN = <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>;
        const TOUR_PROPERTY_ID = <?= (int) $tourPropertyId ?>;
        const PRE_APPROVED = <?= $preApproved ? 'true' : 'false' ?>;
        const sceneOrder = ['aerea', 'acceso', 'lote', 'subestacion', 'maniobras'];

        const staticScenes = [
            {
                id: 'aerea',
                title: 'Vista Aérea (Master Plan & Polígono)',
                image: BASE_TOUR_URL + 'tour/panoramas/industrial/aerea.jpg',
                alt: 'Vista aérea del polígono industrial y vialidades principales',
                desc: 'Polígono regular de 5,000 a 12,500 m² con acceso directo a autopista y corredor logístico de carga pesada.'
            },
            {
                id: 'acceso',
                title: 'Acceso Principal & Caseta de Control',
                image: BASE_TOUR_URL + 'tour/panoramas/industrial/acceso.jpg',
                alt: 'Caseta de control 24/7 y báscula camionera de 80 toneladas',
                desc: 'Caseta blindada con control LPR CCTV, vialidades de concreto hidráulico MR-45 de 22 cm y báscula de 80 toneladas.'
            },
            {
                id: 'lote',
                title: 'Plataforma Nivelada (Huella de Construcción)',
                image: BASE_TOUR_URL + 'tour/panoramas/industrial/lote.jpg',
                alt: 'Plataforma compactada con capacidad portante de 6.5 toneladas por metro cuadrado',
                desc: 'Terraplén compactado al 95% Proctor Modificado con drenaje pluvial subterráneo y capacidad portante de 6.5 ton/m².'
            },
            {
                id: 'subestacion',
                title: 'Subestación Eléctrica 115 kV & Servicios',
                image: BASE_TOUR_URL + 'tour/panoramas/industrial/subestacion.jpg',
                alt: 'Subestación eléctrica industrial de alta tensión y red de gas natural',
                desc: 'Energía escalable hasta 20 MVA en 115 / 13.8 kV, ducto de gas natural de 4" y anillo de fibra óptica.'
            },
            {
                id: 'maniobras',
                title: 'Patio Logístico & Andenes de Carga',
                image: BASE_TOUR_URL + 'tour/panoramas/industrial/maniobras.jpg',
                alt: 'Patio de maniobras de 50 metros para tráileres de 53 pies',
                desc: 'Patio de maniobras de 50 metros preparado para andenes electrohidráulicos de 30,000 lbs.'
            }
        ];

        const scenesConfig = {
            default: {
                firstScene: 'aerea',
                sceneFadeDuration: 600,
                autoLoad: true,
                showZoomCtrl: false,
                showFullscreenCtrl: false,
                compass: true,
                northOffset: 45,
                hfov: 100,
                minHfov: 55,
                maxHfov: 120
            },
            scenes: {
                aerea: {
                    title: 'Vista Aérea (Master Plan & Polígono)',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/industrial/aerea_360.jpg',
                    pitch: -12,
                    yaw: 0,
                    hotSpots: [
                        { pitch: -20, yaw: 0, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'lote', title: 'Bajar a la Plataforma Nivelada' } },
                        { pitch: -18, yaw: 55, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'maniobras', title: 'Ir al Patio Logístico' } },
                        { pitch: -22, yaw: -60, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'acceso', title: 'Ir al Acceso Principal' } },
                        { pitch: -14, yaw: -28, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'subestacion', title: 'Ver Subestación Eléctrica' } },
                        { pitch: -8, yaw: 15, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'POLÍGONO INDUSTRIAL', title: 'Superficie Nivelada', desc: 'Lote regular con frentes a vialidades de alta capacidad y terracerías terminadas.', metric1: { label: 'Área Total', val: '12,500 m²' }, metric2: { label: 'Topografía', val: 'Plana 0.5%' } } }
                    ]
                },
                acceso: {
                    title: 'Acceso Principal & Caseta de Control',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/industrial/acceso_360.jpg',
                    pitch: 0,
                    yaw: 0,
                    hotSpots: [
                        { pitch: 2, yaw: -4, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'lote', title: 'Ingresar al Macrolote' } },
                        { pitch: 18, yaw: -30, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'aerea', title: 'Elevarse a Vista Aérea' } },
                        { pitch: 0, yaw: 42, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'maniobras', title: 'Ir al Patio Logístico' } },
                        { pitch: -3, yaw: 8, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'CONTROL DE ACCESO 24/7', title: 'Caseta Blindada y Báscula Camionera', desc: 'Caseta con CCTV LPR automático y báscula camionera de 80 toneladas.', metric1: { label: 'Báscula', val: '80 Ton' }, metric2: { label: 'Monitoreo', val: '24/7' } } }
                    ]
                },
                lote: {
                    title: 'Plataforma Nivelada (Huella de Construcción)',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/industrial/lote_360.jpg',
                    pitch: -4,
                    yaw: 15,
                    hotSpots: [
                        { pitch: 2, yaw: -60, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'acceso', title: 'Volver a la Caseta' } },
                        { pitch: 1, yaw: 55, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'subestacion', title: 'Ver Subestación Eléctrica' } },
                        { pitch: 16, yaw: 10, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'aerea', title: 'Subir a Vista Aérea' } },
                        { pitch: -16, yaw: 22, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'ESTUDIO GEOTÉCNICO', title: 'Capacidad Portante de 6.5 ton/m²', desc: 'Plataforma compactada al 95% Proctor Modificado ideal para naves industriales y racks automáticos.', metric1: { label: 'Carga', val: '6.5 ton/m²' }, metric2: { label: 'Compactación', val: '95% P.M.' } } }
                    ]
                },
                subestacion: {
                    title: 'Subestación Eléctrica & Servicios',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/industrial/subestacion_360.jpg',
                    pitch: 0,
                    yaw: -10,
                    hotSpots: [
                        { pitch: -2, yaw: -45, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'lote', title: 'Regresar a Plataforma' } },
                        { pitch: 18, yaw: 0, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'aerea', title: 'Vista Panorámica Aérea' } },
                        { pitch: -3, yaw: 70, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'maniobras', title: 'Ir al Patio Logístico' } },
                        { pitch: -2, yaw: -8, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'ENERGÍA EN ALTA TENSIÓN', title: 'Subestación 115 kV / 13.8 kV', desc: 'Transformadores dedicados, potencia escalable hasta 20 MVA y doble circuito de respaldo.', metric1: { label: 'Tensión', val: '115 / 13.8 kV' }, metric2: { label: 'Potencia', val: '20 MVA' } } }
                    ]
                },
                maniobras: {
                    title: 'Patio Logístico & Andenes de Carga',
                    type: 'equirectangular',
                    panorama: BASE_TOUR_URL + 'tour/panoramas/industrial/maniobras_360.jpg',
                    pitch: -2,
                    yaw: -15,
                    hotSpots: [
                        { pitch: -1, yaw: -75, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'lote', title: 'Ir a Plataforma' } },
                        { pitch: 20, yaw: -5, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'aerea', title: 'Subir a Vista Aérea' } },
                        { pitch: -3, yaw: 45, type: 'custom', createTooltipFunc: createNavHotspot, createTooltipArgs: { targetScene: 'acceso', title: 'Salir a Caseta' } },
                        { pitch: -5, yaw: -25, type: 'custom', createTooltipFunc: createInfoHotspot, createTooltipArgs: { tag: 'LOGÍSTICA PESADA', title: 'Patio de Maniobras de 50 Metros', desc: 'Losas de concreto hidráulico para maniobra y acoplamiento de remolques de 53 pies.', metric1: { label: 'Fondo', val: '50 m' }, metric2: { label: 'Capacidad', val: 'Full 53ft' } } }
                    ]
                }
            }
        };

        let viewer = null;
        let isAutoRotating = false;
        let currentSceneId = 'aerea';
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
            hotSpotDiv.innerHTML = '<span aria-hidden="true">ℹ</span><div class="hotspot-tooltip">' + args.title + '</div>';
            const trigger = function (e) {
                e.stopPropagation();
                openInfoModal(args.tag, args.title, args.desc, args.metric1, args.metric2);
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
            }, 200);
        }

        function prevScene() {
            const idx = sceneOrder.indexOf(currentSceneId);
            switchScene(sceneOrder[(idx - 1 + sceneOrder.length) % sceneOrder.length]);
        }

        function nextScene() {
            const idx = sceneOrder.indexOf(currentSceneId);
            switchScene(sceneOrder[(idx + 1) % sceneOrder.length]);
        }

        function updateHudState(sceneId) {
            const scene = scenesConfig.scenes[sceneId];
            if (!scene) return;
            const zoneNameEl = document.getElementById('currentZoneName');
            if (zoneNameEl) zoneNameEl.textContent = scene.title;
            document.querySelectorAll('.zone-tab').forEach(function (tab) {
                tab.classList.toggle('active', tab.getAttribute('data-scene') === sceneId);
            });
            const pinMap = {
                aerea: 'mapPinAerea',
                acceso: 'mapPinAcceso',
                lote: 'mapPinLote',
                subestacion: 'mapPinSubestacion',
                maniobras: 'mapPinManiobras'
            };
            document.querySelectorAll('.minimap-pin').forEach(function (pin) {
                pin.setAttribute('stroke', 'none');
            });
            const activePin = document.getElementById(pinMap[sceneId]);
            if (activePin) {
                activePin.setAttribute('stroke', '#ffffff');
                activePin.setAttribute('stroke-width', '2');
            }
        }

        function panView(dPitch, dYaw) {
            if (!viewer) return;
            viewer.setPitch(Math.max(-85, Math.min(85, viewer.getPitch() + dPitch)));
            viewer.setYaw(viewer.getYaw() + dYaw);
        }

        function resetCameraView() {
            if (!viewer) return;
            const scene = scenesConfig.scenes[currentSceneId] || scenesConfig.scenes.aerea;
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
                viewer.startAutoRotate(-1.8);
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

        function toggleMinimap() {
            document.getElementById('minimapCard').classList.toggle('active');
            document.getElementById('btnMinimap').classList.toggle('active');
        }

        function openInfoModal(tag, title, desc, metric1, metric2) {
            document.getElementById('infoTag').textContent = tag || 'ESPECIFICACIÓN TÉCNICA';
            document.getElementById('infoTitle').textContent = title;
            document.getElementById('infoDesc').textContent = desc;
            const m1 = metric1 || { label: 'Capacidad', val: '6.5 ton/m²' };
            const m2 = metric2 || { label: 'Norma', val: 'I-3 Pesada' };
            document.getElementById('metricLabel1').textContent = m1.label;
            document.getElementById('metricVal1').textContent = m1.val;
            document.getElementById('metricLabel2').textContent = m2.label;
            document.getElementById('metricVal2').textContent = m2.val;
            document.getElementById('infoModalOverlay').classList.add('active');
        }

        function closeInfoModal() { document.getElementById('infoModalOverlay').classList.remove('active'); }
        function openFichaModal() { document.getElementById('fichaModalOverlay').classList.add('active'); }
        function closeFichaModal() { document.getElementById('fichaModalOverlay').classList.remove('active'); }
        function openHelpModal() { document.getElementById('helpModalOverlay').classList.add('active'); }
        function closeHelpModal() { document.getElementById('helpModalOverlay').classList.remove('active'); }
        function closeContactModal() { document.getElementById('contactModalOverlay').classList.remove('active'); }

        function contactAgent() {
            closeFichaModal();
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ type: 'infonatec-open-interest', propertyId: TOUR_PROPERTY_ID }, window.location.origin);
                return;
            }
            document.getElementById('contactModalOverlay').classList.add('active');
        }

        async function submitStandaloneContact(event) {
            event.preventDefault();
            const statusEl = document.getElementById('indContactStatus');
            statusEl.textContent = 'Enviando solicitud...';
            try {
                const res = await fetch(BASE_TOUR_URL + 'api/contacto', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        tipo: 'TOUR_SOLICITUD',
                        id_propiedad: TOUR_PROPERTY_ID,
                        nombre: document.getElementById('indContactName').value.trim(),
                        telefono: document.getElementById('indContactPhone').value.trim(),
                        correo: document.getElementById('indContactEmail').value.trim(),
                        mensaje: document.getElementById('indContactMsg').value.trim()
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    statusEl.textContent = data.message || 'No se pudo enviar la solicitud.';
                    return;
                }
                statusEl.textContent = data.message || 'Solicitud enviada correctamente.';
                setTimeout(function () { closeContactModal(); }, 1500);
            } catch (err) {
                statusEl.textContent = 'Error de conexión al enviar la solicitud.';
            }
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

        window.addEventListener('keydown', function (e) {
            const activeTag = document.activeElement ? document.activeElement.tagName : '';
            if (activeTag === 'INPUT' || activeTag === 'TEXTAREA') return;

            const staticOpen = document.getElementById('staticGalleryOverlay').classList.contains('active');
            const helpOpen = document.getElementById('helpModalOverlay').classList.contains('active');

            if (e.key === 'Escape') {
                closeInfoModal();
                closeFichaModal();
                closeHelpModal();
                closeStaticGallery();
                closeContactModal();
                const card = document.getElementById('minimapCard');
                if (card.classList.contains('active')) toggleMinimap();
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
            else if (e.key === 'm' || e.key === 'M') { toggleMinimap(); }
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

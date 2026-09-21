<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO básico -->
    <title><?= htmlspecialchars($pageTitle ?? APP_TITLE) ?></title>
    <meta name="description" content="INFONATEC — Plataforma inmobiliaria para comprar, rentar o publicar terrenos, casas y departamentos de manera sencilla, transparente y segura en México.">
    <meta name="robots" content="index, follow">

    <!-- Open Graph (redes sociales) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? APP_TITLE) ?>">
    <meta property="og:description" content="Adquiere, renta o publica terrenos, casas y departamentos de manera sencilla, transparente y segura.">
    <meta property="og:image" content="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80">
    <meta property="og:locale" content="es_MX">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">

    <!-- Base URL para JavaScript -->
    <script>
        window.APP_BASE_URL = "<?= BASE_URL ?>";
    </script>

    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles.css?v=5.0">

    <style>
    /* =========================================================
       INTRO SPLASH
    ========================================================= */
    #intro-splash {
        position: fixed;
        inset: 0;
        z-index: 99999;
        background: #0d1015;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        padding: 12vh 20px 15vh;
        overflow: hidden;
        transition: opacity 1.3s ease-in-out, visibility 1.3s ease-in-out;
    }

    #intro-splash.fade-out {
        opacity: 0;
        visibility: hidden;
    }

    #intro-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .55;
        pointer-events: none;
        z-index: 1;
    }

    .intro-logo {
        position: relative;
        z-index: 2;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        animation: introLogoPop 1.1s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .intro-logo-name {
        margin-top: 18px;
        font-family: Georgia, serif;
        font-size: clamp(36px, 5.5vw, 64px);
        font-weight: 700;
        letter-spacing: 7px;
        color: #f1ede6;
        text-transform: uppercase;
        text-shadow: 0 4px 30px rgba(0, 0, 0, .85);
    }

    .intro-logo-sub {
        margin-top: 6px;
        font-size: clamp(12px, 1.4vw, 15px);
        font-weight: 400;
        letter-spacing: 4px;
        color: #c9a86a;
        text-transform: uppercase;
    }

    .intro-actions {
        position: relative;
        z-index: 2;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        justify-content: center;
        animation: introBtnPop 1.3s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .intro-btn {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 16px 36px;
        border-radius: 40px;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.35s ease;
        text-decoration: none;
        background: rgba(20, 24, 33, 0.75);
        color: #f1ede6;
        border: 1.5px solid rgba(201, 168, 106, 0.5);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45);
    }

    .intro-btn:hover {
        background: #c9a86a;
        color: #172033;
        border-color: #c9a86a;
        transform: scale(1.05);
    }

    .intro-btn svg {
        width: 22px;
        height: 22px;
        fill: none;
        stroke: currentColor;
    }

    @keyframes introLogoPop {
        from { opacity: 0; transform: scale(.88) translateY(18px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    @keyframes introBtnPop {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Estilos del catálogo e interacción */
    .filter-btn {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, .28);
        color: #fff;
        padding: 10px 25px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }
    .filter-btn:hover, .filter-btn.active {
        background: #c9a86a;
        border-color: #c9a86a;
        color: white;
    }
    #adquirir {
        padding: 110px 5% 80px !important;
        background: #f5f7fb !important;
    }
    .category-chooser {
        min-height: 100vh;
        min-height: 100dvh;
        margin: -110px -5% -80px;
        padding: 145px 5% 70px;
        background: #141414;
        color: #fff;
    }
    .chooser-header {
        display: flex;
        justify-content: space-between;
        gap: 40px;
        align-items: end;
        margin: 0 auto 42px;
        max-width: 1420px;
    }
    .chooser-kicker { color: #c9a86a; font-size: 10px; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 16px; }
    .chooser-header h2 { font: normal clamp(38px, 5vw, 64px) Georgia, serif; }
    .chooser-header p { max-width: 410px; color: #c7d0df; line-height: 1.8; }
    .chooser-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px; max-width: 1420px; margin: auto; }
    .chooser-card { position: relative; min-height: clamp(330px, 48vh, 500px); overflow: hidden; display: flex; align-items: end; padding: 32px; border: 1px solid rgba(255,255,255,.15); border-radius: 18px; isolation: isolate; background: center / cover; color: #fff; text-align: left; font: inherit; cursor: pointer; transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
    .chooser-card::before { content: ""; position: absolute; inset: 0; background: linear-gradient(transparent 35%, rgba(0,0,0,.86)); border-radius: inherit; }
    .chooser-card:hover, .chooser-card:focus-visible { transform: translateY(-6px); border-color: #c9a86a; box-shadow: 0 16px 36px rgba(0,0,0,.45); outline: none; }
    .chooser-card span { position: relative; z-index: 1; }
    .chooser-card strong { display: block; font: normal clamp(32px, 3vw, 46px) Georgia, serif; margin-bottom: 8px; }
    .chooser-card small { font-size: 14px; }
    .catalog-view[hidden], .category-chooser[hidden] { display: none; }
    @media (max-width: 800px) { .chooser-header { display: block; } .chooser-header p { margin-top: 22px; } .chooser-grid { grid-template-columns: 1fr; } .chooser-card { min-height: 280px; } }
    .nav-operation-tabs {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .nav-operation-tabs .filter-btn {
        padding: 9px 20px !important;
        font-size: 12px;
        letter-spacing: .5px;
    }
    @media (max-width: 900px) {
        .nav-operation-tabs { margin-left: auto; }
        .nav-operation-tabs .filter-btn { padding: 8px 12px !important; font-size: 11px; }
    }
    @media (max-width: 650px) {
        .navbar .custom-dropdown { display: none; }
        .nav-operation-tabs .filter-btn { padding: 8px 9px !important; font-size: 10px; }
    }
    #adquirir .property-card {
        background: #181d2f;
        border-radius: 12px;
        box-shadow: 0 5px 18px rgba(22, 34, 54, .14);
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
        color: #fff;
    }
    #adquirir .property-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, .25);
    }
    #adquirir .property-image {
        height: 220px;
        position: relative;
        overflow: hidden;
    }
    .property-image::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,.1), transparent 50%, rgba(0,0,0,.6));
        pointer-events: none;
    }
    .property-image video {
        transition: transform 0.6s ease;
    }
    #adquirir .property-card:hover .property-image video {
        transform: scale(1.08);
    }
    .property-tag {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #c9a86a;
        color: white;
        padding: 6px 14px;
        font-size: 11px;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-weight: 800;
        border-radius: 20px;
        z-index: 2;
        box-shadow: 0 4px 10px rgba(201,168,106,0.3);
    }
    .favorite-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 2;
        transition: 0.3s;
    }
    .favorite-btn:hover {
        background: #fff;
        color: #ff3b30;
        transform: scale(1.1);
    }
    .favorite-btn.active { color: #ff3b30; }
    .favorite-btn.active svg { fill: #ff3b30; }
    #adquirir .property-body { background: #181d2f; }
    #adquirir .property-title { color: #fff !important; }
    #adquirir .property-location { color: #a0a8b9 !important; margin-bottom: 0; }
    #adquirir .property-price { color: #c9a86a; }
    #adquirir .property-stats { display: none !important; }

    .custom-dropdown {
        position: relative;
        display: inline-block;
    }
    .dropdown-toggle {
        background: transparent;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.3);
        padding: 10px 20px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        backdrop-filter: blur(5px);
    }
    .dropdown-toggle:hover {
        background: #c9a86a;
        border-color: #c9a86a;
        box-shadow: 0 4px 15px rgba(201,168,106,0.3);
    }
    </style>
</head>

<body>

<?php if (!empty($showSplash)): ?>
<!-- =========================================================
     INTRO SPLASH
========================================================= -->
<div id="intro-splash" role="dialog" aria-label="Pantalla de bienvenida INFONATEC" aria-modal="true">

    <!-- Video de fondo — loop -->
    <video
        id="intro-video"
        src="<?= BASE_URL ?>inicio de mi sistema web.mp4"
        autoplay
        muted
        playsinline
        loop
        preload="auto"
    ></video>

    <!-- Logo superior -->
    <div class="intro-logo">
        <svg xmlns="http://www.w3.org/2000/svg" width="90" height="90" viewBox="0 0 38 38" fill="none" aria-hidden="true">
            <rect width="38" height="38" rx="9" fill="#c9a86a"/>
            <rect x="11" y="20" width="16" height="11" rx="1.5" fill="white"/>
            <path d="M7 21 L19 10 L31 21" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            <rect x="16" y="24" width="6" height="7" rx="1" fill="#c9a86a"/>
        </svg>
        <div class="intro-logo-name">INFONATEC</div>
        <div class="intro-logo-sub">Inmobiliaria</div>
    </div>

    <!-- Botones de Acción inferiores -->
    <div class="intro-actions">
        <button class="intro-btn" onclick="closeSplash('comprar')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            Adquirir
        </button>
        <a class="intro-btn" href="<?= BASE_URL ?>vender">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                <line x1="7" y1="7" x2="7.01" y2="7"></line>
            </svg>
            Venta
        </a>
    </div>

</div>
<?php endif; ?>

<!-- =========================================================
     MENÚ MÓVIL
========================================================= -->
<div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true" aria-label="Menú de navegación">
    <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Cerrar menú">×</button>
    <a href="<?= BASE_URL ?>#adquirir">Explorar</a>
    <a href="<?= BASE_URL ?>vender">Vender</a>
    <a href="<?= BASE_URL ?>#asesor">Asesor</a>
    <a href="<?= BASE_URL ?>#legal">Legal</a>
</div>

<!-- =========================================================
     NAVBAR
========================================================= -->
<nav class="navbar" id="navbar" role="navigation" aria-label="Navegación principal">

    <div style="display: flex; align-items: center; gap: 20px;">
        <a href="<?= BASE_URL ?>" class="logo" aria-label="INFONATEC Inmobiliaria - Inicio">
            <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 38 38" fill="none" aria-hidden="true">
                <rect width="38" height="38" rx="9" fill="#c9a86a"/>
                <rect x="11" y="20" width="16" height="11" rx="1.5" fill="white"/>
                <path d="M7 21 L19 10 L31 21" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <rect x="16" y="24" width="6" height="7" rx="1" fill="#c9a86a"/>
            </svg>
        </a>

        <?php if (($activePage ?? '') === 'home'): ?>
        <div class="nav-operation-tabs" aria-label="Tipo de operación">
            <button class="filter-btn active" id="op-comprar" onclick="openCatalogForOperation('comprar')">Adquirir</button>
            <button class="filter-btn" id="op-rentar" onclick="openCatalogForOperation('rentar')">Rentar</button>
        </div>
        <?php endif; ?>
    </div>

    <ul class="nav-links" role="list">
        <li><a href="<?= BASE_URL ?>#adquirir">Explorar</a></li>
        <li><a href="<?= BASE_URL ?>vender">Vender</a></li>
        <li><a href="<?= BASE_URL ?>#asesor">Asesor</a></li>
        <li><a href="<?= BASE_URL ?>#legal">Legal</a></li>
    </ul>

    <div class="nav-actions">
        <!-- Botones visibles cuando NO hay sesión activa -->
        <div id="navAuthButtons" style="display:flex;gap:10px;align-items:center;">
            <button class="btn-outline" onclick="openModal('loginModal')">Ingresar</button>
            <button class="btn-light" onclick="openModal('registerModal')">Registrarme</button>
        </div>

        <!-- Área visible cuando SÍ hay sesión activa (poblada por JS o PHP) -->
        <div id="navUserArea" style="display:none;align-items:center;gap:10px;"></div>

        <button
            class="nav-hamburger"
            id="hamburgerBtn"
            aria-label="Abrir menú de navegación"
            aria-expanded="false"
            aria-controls="mobileMenu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

</nav>

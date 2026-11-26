<?php
$csrfToken = Security::csrfToken();
$currentUser = Usuario::current();
$safeUser = null;
$userFavoriteIds = [];
$adminNavUser = null;

if (!empty($currentUser['id'])) {
    $safeUser = [
        'id'       => (int) $currentUser['id'],
        'name'     => (string) ($currentUser['name'] ?? ''),
        'last'     => (string) ($currentUser['last'] ?? ''),
        'email'    => (string) ($currentUser['email'] ?? ''),
        'phone'    => (string) ($currentUser['phone'] ?? ''),
        'is_admin' => !empty($currentUser['is_admin']),
    ];
    $userFavoriteIds = (new Propiedad())->getUserFavoriteIds((int) $currentUser['id']);

    $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
    if ($adminEmail === '' || strtolower((string) ($currentUser['email'] ?? '')) === $adminEmail) {
        $adminDbConn = Database::getConnection();
        if ($adminDbConn !== null) {
            try {
                $adminStmt = $adminDbConn->prepare(
                    "SELECT r.nombre AS rol
                     FROM usuario_rol ur
                     INNER JOIN rol r ON r.id_rol = ur.id_rol
                     INNER JOIN usuario u ON u.id_usuario = ur.id_usuario
                     WHERE ur.id_usuario = ? AND u.activo = 1 AND r.nombre = 'ADMIN'
                     LIMIT 1"
                );
                $adminStmt->execute([(int) $currentUser['id']]);
                if ($adminStmt->fetch()) {
                    $adminNavUser = $safeUser;
                    $safeUser['is_admin'] = true;
                }
            } catch (Exception $e) {
                error_log('Error verificando rol admin en header: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <!-- SEO básico -->
    <title><?= htmlspecialchars($pageTitle ?? APP_TITLE, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="INFONATEC — Plataforma inmobiliaria para comprar, rentar o publicar terrenos, casas y departamentos de manera sencilla, transparente y segura en México.">
    <meta name="robots" content="index, follow">

    <!-- Open Graph (redes sociales) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? APP_TITLE, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="Adquiere, renta o publica terrenos, casas y departamentos de manera sencilla, transparente y segura.">
    <meta property="og:image" content="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=80">
    <meta property="og:locale" content="es_MX">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">

    <!-- Configuración segura para JavaScript (sin credenciales ni contraseñas) -->
    <script>
        window.APP_BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
        window.CSRF_TOKEN = <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>;
        window.INFONATEC_USER = <?= json_encode($safeUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.INFONATEC_FAVORITES = <?= json_encode($userFavoriteIds) ?>;
    </script>

    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles.css?v=6.5">
</head>

<body class="page-<?= htmlspecialchars($activePage ?? 'public', ENT_QUOTES, 'UTF-8') ?>">

<a href="#main-content" class="skip-link">Saltar al contenido principal</a>

<?php if (!empty($showSplash)): ?>
<!-- =========================================================
     INTRO SPLASH
========================================================= -->
<div id="intro-splash" role="dialog" aria-label="Pantalla de bienvenida INFONATEC" aria-modal="true">

    <video
        id="intro-video"
        src="<?= BASE_URL ?>media/intro-infonatec.mp4"
        autoplay
        muted
        playsinline
        loop
        preload="metadata"
    ></video>

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

    <div class="intro-actions">
        <button type="button" class="intro-btn" onclick="closeSplash('comprar')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            Adquirir
        </button>
        <a class="intro-btn" href="<?= BASE_URL ?>vender">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
    <button type="button" class="mobile-menu-close" id="mobileMenuClose" aria-label="Cerrar menú">×</button>
    <a href="<?= BASE_URL ?>explorar">Explorar</a>
    <a href="<?= BASE_URL ?>vender">Vender</a>
    <?php if ($safeUser): ?><a href="<?= BASE_URL ?>mis-propiedades">Mis propiedades</a><?php endif; ?>
    <a href="<?= BASE_URL ?>#asesor">Asesor</a>
    <a href="<?= BASE_URL ?>#legal">Legal</a>
    <?php if (!empty($adminNavUser)): ?>
    <a href="<?= BASE_URL ?>admin">Administración</a>
    <?php endif; ?>
</div>

<!-- =========================================================
     NAVBAR
========================================================= -->
<nav class="navbar" id="navbar" role="navigation" aria-label="Navegación principal">

    <div class="nav-brand-group">
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
            <a href="<?= BASE_URL ?>explorar?operacion=comprar" class="filter-btn active" id="op-comprar">Adquirir</a>
            <a href="<?= BASE_URL ?>explorar?operacion=rentar" class="filter-btn" id="op-rentar">Rentar</a>
        </div>
        <?php endif; ?>
    </div>

    <ul class="nav-links" role="list">
        <li><a href="<?= BASE_URL ?>explorar" class="<?= ($activePage ?? '') === 'explorar' ? 'nav-link-active' : '' ?>">Explorar</a></li>
        <li><a href="<?= BASE_URL ?>vender" class="<?= ($activePage ?? '') === 'vender' ? 'nav-link-active' : '' ?>">Vender</a></li>
        <?php if ($safeUser): ?><li><a href="<?= BASE_URL ?>mis-propiedades" class="<?= ($activePage ?? '') === 'mis-propiedades' ? 'nav-link-active' : '' ?>">Mis propiedades</a></li><?php endif; ?>
        <li><a href="<?= BASE_URL ?>#asesor">Asesor</a></li>
        <li><a href="<?= BASE_URL ?>#legal">Legal</a></li>
        <?php if (!empty($adminNavUser)): ?>
        <li><a href="<?= BASE_URL ?>admin" class="<?= ($activePage ?? '') === 'admin' ? 'nav-link-active' : '' ?>">Administración</a></li>
        <?php endif; ?>
    </ul>

    <div class="nav-actions">
        <div id="navAuthButtons" style="display:<?= $safeUser ? 'none' : 'flex' ?>;gap:10px;align-items:center;">
            <button type="button" class="btn-outline" onclick="openModal('loginModal')">Ingresar</button>
            <button type="button" class="btn-light" onclick="openModal('registerModal')">Registrarme</button>
        </div>

        <div id="navUserArea" style="display:<?= $safeUser ? 'flex' : 'none' ?>;align-items:center;gap:10px;">
            <?php if ($safeUser): ?>
            <span class="nav-user-greeting">
                Hola, <strong><?= htmlspecialchars(explode(' ', trim($safeUser['name']))[0], ENT_QUOTES, 'UTF-8') ?></strong>
            </span>
            <button type="button" class="btn-outline" id="logoutBtn">Cerrar sesión</button>
            <?php endif; ?>
        </div>

        <button
            type="button"
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

<!-- Aviso de estado de red / conexión lenta -->
<div id="networkStatusBanner" class="network-banner" role="status" aria-live="polite" hidden>
    <span id="networkStatusIcon" aria-hidden="true">📶</span>
    <span id="networkStatusText"></span>
    <button type="button" class="network-banner-close" onclick="dismissNetworkBanner()" aria-label="Ocultar aviso de red">×</button>
</div>

<main id="main-content" tabindex="-1">

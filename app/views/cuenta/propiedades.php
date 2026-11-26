<?php $esc = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<main id="main-content" class="owner-page">
    <div class="owner-heading"><div><p class="owner-eyebrow">Tu cuenta</p><h1>Mis propiedades</h1><p>Consulta tus anuncios, actualiza sus datos o retíralos del catálogo.</p></div>
        <?php if ($owner): ?><a class="btn-primary" href="<?= BASE_URL ?>vender">Publicar propiedad</a><?php endif; ?></div>
    <?php if (!empty($flash)): ?><p class="owner-notice" role="status"><?= $esc($flash['message']) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="owner-notice" role="alert"><?= $esc($error) ?></p><?php endif; ?>
    <?php if (!$owner): ?>
        <section class="owner-empty"><h2>Ingresa a tu cuenta</h2><p>Tus publicaciones están vinculadas a la cuenta con la que las creaste.</p>
            <button class="btn-primary" type="button" onclick="openModal('loginModal')">Iniciar sesión</button>
            <button class="btn-outline" type="button" onclick="openModal('registerModal')">Crear cuenta</button>
            <p>Si publicaste antes sin cuenta y no tienes contraseña, contacta a administración para verificar tu identidad y habilitar tu acceso. No crees otra cuenta para recuperar ese anuncio.</p>
        </section>
    <?php elseif (!$listings && empty($error)): ?>
        <section class="owner-empty"><h2>Aún no tienes publicaciones</h2><p>Cuando publiques con esta cuenta, tus propiedades aparecerán aquí cada vez que inicies sesión.</p><a class="btn-primary" href="<?= BASE_URL ?>vender">Crear mi primer anuncio</a></section>
    <?php endif; ?>
    <div class="owner-grid">
    <?php foreach ($listings as $listing): $withdrawn = $listing['fecha_retiro'] !== null; ?>
        <article class="owner-card">
            <img src="<?= $esc(Propiedad::resolveAssetUrl($listing['imagen'])) ?>" alt="<?= $esc($listing['titulo']) ?>" loading="lazy">
            <div class="owner-card-body"><p class="owner-eyebrow"><?= $esc($withdrawn ? 'RETIRADA' : $listing['estado']) ?> · <?= $esc($listing['operacion']) ?></p>
                <h2><?= $esc($listing['titulo']) ?></h2><p><?= $esc($listing['ubicacion']) ?></p><p class="owner-price">$<?= number_format((float) $listing['precio'], 2) ?> MXN<?= $listing['operacion'] === 'RENTA' ? ' / mes' : '' ?></p>
                <a class="owner-tour-link" href="<?= BASE_URL ?>mis-propiedades/<?= (int)$listing['id_propiedad'] ?>/recorrido" aria-label="Crear o editar recorrido de <?= $esc($listing['titulo']) ?>">
                    <span class="owner-tour-icon" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="8" cy="8" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/></svg></span>
                    <span class="owner-tour-text"><strong>Diseña tu recorrido</strong><span>Crea o edita los espacios con fotos o video</span></span>
                    <span class="owner-tour-arrow" aria-hidden="true">↗</span>
                </a>
                <?php if (!$withdrawn && $listing['estado'] === 'DISPONIBLE'): ?>
                <div class="owner-actions"><a class="btn-primary" href="<?= BASE_URL ?>mis-propiedades/<?= (int) $listing['id_propiedad'] ?>/editar">Editar</a>
                    <form method="post" action="<?= BASE_URL ?>mis-propiedades/<?= (int) $listing['id_propiedad'] ?>/retirar" onsubmit="return confirm('¿Retirar esta propiedad del catálogo? Se conservará en tu historial.');">
                        <input type="hidden" name="_csrf" value="<?= $esc(Security::csrfToken()) ?>"><button class="btn-outline" type="submit">Retirar anuncio</button>
                    </form></div>
                <?php else: ?><p>Este anuncio se conserva en tu historial y no permite cambios.</p><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</main>

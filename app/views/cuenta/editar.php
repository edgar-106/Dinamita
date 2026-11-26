<?php
$esc = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$old = $_SESSION['publication_old_input'] ?? [];
unset($_SESSION['publication_old_input']);
$values = array_replace($listing, $old);
$locked = $listing['fecha_retiro'] !== null || $listing['estado'] !== 'DISPONIBLE';
?>
<main id="main-content" class="owner-page">
    <a href="<?= BASE_URL ?>mis-propiedades">← Volver a mis propiedades</a>
    <h1>Editar propiedad</h1><p>Actualiza la información de tu anuncio. Sus fotografías y recorridos se conservan.</p>
    <?php if (!empty($flash)): ?><p class="owner-notice" role="alert"><?= $esc($flash['message']) ?></p><?php endif; ?>
    <?php if ($locked): ?><p class="owner-notice">Este anuncio ya no está disponible para cambios.</p>
    <?php else: ?>
    <form class="owner-edit" method="post" action="<?= BASE_URL ?>mis-propiedades/<?= (int) $listing['id_propiedad'] ?>/editar">
        <input type="hidden" name="_csrf" value="<?= $esc(Security::csrfToken()) ?>">
        <label>Título<input name="titulo" required minlength="5" maxlength="180" value="<?= $esc($values['titulo']) ?>"></label>
        <label>Ubicación<input name="ubicacion" required minlength="4" maxlength="255" value="<?= $esc($values['ubicacion']) ?>"></label>
        <label>Operación<select name="operacion"><option value="VENTA" <?= $values['operacion'] === 'VENTA' ? 'selected' : '' ?>>Venta</option><option value="RENTA" <?= $values['operacion'] === 'RENTA' ? 'selected' : '' ?>>Renta</option></select></label>
        <label>Precio (MXN)<input name="precio" type="number" min="0.01" step="0.01" required value="<?= $esc($values['precio']) ?>"></label>
        <label>Superficie (m²)<input name="superficie" type="number" min="0.01" step="0.01" required value="<?= $esc($values['superficie']) ?>"></label>
        <?php if ($listing['habitaciones'] !== null || $listing['banos'] !== null): ?>
        <label>Habitaciones<input name="habitaciones" type="number" min="0" max="50" required value="<?= $esc($values['habitaciones']) ?>"></label>
        <label>Baños<input name="banos" type="number" min="0" max="50" required value="<?= $esc($values['banos']) ?>"></label>
        <?php endif; ?>
        <label class="owner-wide">Descripción<textarea name="descripcion" required minlength="20" rows="7"><?= $esc($values['descripcion']) ?></textarea></label>
        <div class="owner-actions owner-wide"><button class="btn-primary" type="submit">Guardar cambios</button><a class="btn-outline" href="<?= BASE_URL ?>mis-propiedades/<?= (int) $listing['id_propiedad'] ?>/recorrido-video">Recorrido por video</a><a class="btn-outline" href="<?= BASE_URL ?>mis-propiedades">Cancelar</a></div>
    </form>
    <?php endif; ?>
</main>

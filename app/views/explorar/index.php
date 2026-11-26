<?php
$uniqueLocations = [];
foreach ($properties ?? [] as $p) {
    $loc = trim((string) ($p['location'] ?? ''));
    if ($loc !== '' && !in_array($loc, $uniqueLocations, true)) {
        $uniqueLocations[] = $loc;
    }
}
sort($uniqueLocations);

$initOp = $currentOperation ?? 'all';
$initType = $currentType ?? 'all';
$initLocation = $currentLocation ?? '';
$initMinPrice = $currentMinPrice ?? '';
$initMaxPrice = $currentMaxPrice ?? '';
$initFavOnly = !empty($onlyFavorites);

$titlesMap = [
    'all' => ['title' => 'Explorar Catálogo', 'desc' => 'Encuentra casas, departamentos y terrenos verificados con galería completa y contacto directo.'],
    'casa' => ['title' => 'Casas', 'desc' => 'Encuentra la casa de tus sueños, ideal para ti y tu familia.'],
    'departamento' => ['title' => 'Departamentos', 'desc' => 'Espacios modernos y céntricos, adaptados a tu estilo de vida.'],
    'terreno' => ['title' => 'Terrenos', 'desc' => 'Lotes residenciales, comerciales e industriales para construir tus proyectos.'],
];
$heroMeta = $titlesMap[$initType] ?? $titlesMap['all'];
?>

<section class="explorar-header">
    <img
        class="header-background"
        src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=80"
        alt=""
        aria-hidden="true"
        loading="eager"
        onerror="this.style.display='none'"
    >
    <h1 id="catTitle"><?= htmlspecialchars($heroMeta['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p id="catDesc"><?= htmlspecialchars($heroMeta['desc'], ENT_QUOTES, 'UTF-8') ?></p>

    <div class="operation-tabs" role="group" aria-label="Filtrar por tipo de operación">
        <button type="button" class="op-btn js-filter-op <?= $initOp === 'all' ? 'active' : '' ?>" data-op="all">Todas</button>
        <button type="button" class="op-btn js-filter-op <?= $initOp === 'comprar' ? 'active' : '' ?>" data-op="comprar">Comprar</button>
        <button type="button" class="op-btn js-filter-op <?= $initOp === 'rentar' ? 'active' : '' ?>" data-op="rentar">Rentar</button>
    </div>

    <div class="category-tabs" role="group" aria-label="Filtrar por categoría">
        <button type="button" class="tab-btn js-filter-type <?= $initType === 'all' ? 'active' : '' ?>" data-type="all">Todas</button>
        <button type="button" class="tab-btn js-filter-type <?= $initType === 'casa' ? 'active' : '' ?>" data-type="casa">Casas</button>
        <button type="button" class="tab-btn js-filter-type <?= $initType === 'departamento' ? 'active' : '' ?>" data-type="departamento">Departamentos</button>
        <button type="button" class="tab-btn js-filter-type <?= $initType === 'terreno' ? 'active' : '' ?>" data-type="terreno">Terrenos</button>
    </div>
</section>

<section
    class="properties-container"
    id="explorarCatalogView"
    data-catalog-scope="explorar"
    data-init-op="<?= htmlspecialchars($initOp, ENT_QUOTES, 'UTF-8') ?>"
    data-init-type="<?= htmlspecialchars($initType, ENT_QUOTES, 'UTF-8') ?>"
    data-init-fav="<?= $initFavOnly ? '1' : '0' ?>"
>
    <div class="explorar-inner">
        <!-- Barra de filtros avanzados sin recarga -->
        <div class="catalog-filters-bar catalog-filters-bar-light" role="region" aria-label="Filtros por ubicación, precio y favoritos">
            <div class="filter-inputs-row">
                <div class="filter-field">
                    <label for="explorarLocation">Ubicación o palabra clave</label>
                    <input
                        type="search"
                        id="explorarLocation"
                        class="js-filter-location"
                        list="explorarLocationsList"
                        value="<?= htmlspecialchars($initLocation, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ej. Querétaro, CDMX, Monterrey..."
                    >
                </div>
                <div class="filter-field">
                    <label for="explorarMinPrice">Precio mín. (MXN)</label>
                    <input
                        type="number"
                        id="explorarMinPrice"
                        class="js-filter-min-price"
                        min="0"
                        step="10000"
                        value="<?= htmlspecialchars($initMinPrice, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="0"
                    >
                </div>
                <div class="filter-field">
                    <label for="explorarMaxPrice">Precio máx. (MXN)</label>
                    <input
                        type="number"
                        id="explorarMaxPrice"
                        class="js-filter-max-price"
                        min="0"
                        step="10000"
                        value="<?= htmlspecialchars($initMaxPrice, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Sin límite"
                    >
                </div>
                <div class="filter-field">
                    <label for="explorarSort">Ordenar por</label>
                    <select id="explorarSort" class="js-filter-sort">
                        <option value="default">Más recientes</option>
                        <option value="price-asc">Menor precio</option>
                        <option value="price-desc">Mayor precio</option>
                    </select>
                </div>
                <div class="filter-field filter-field-fav">
                    <button type="button" class="filter-fav-toggle js-filter-fav <?= $initFavOnly ? 'active' : '' ?>" aria-pressed="<?= $initFavOnly ? 'true' : 'false' ?>">
                        ♥ Favoritos (<span class="js-fav-count">0</span>)
                    </button>
                </div>
            </div>
        </div>

        <datalist id="explorarLocationsList">
            <?php foreach ($uniqueLocations as $loc): ?>
            <option value="<?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <!-- Etiquetas de filtros activos removibles + contador -->
        <div class="filter-status-bar filter-status-bar-light">
            <div id="activeFilterChips" class="filter-chips-bar js-active-chips" role="region" aria-label="Filtros activos"></div>
            <div id="catalogResultCount" class="catalog-result-count js-result-count" aria-live="polite"></div>
        </div>

        <!-- Cuadrícula de propiedades -->
        <div class="properties-grid" id="propertyGrid">
            <?php foreach ($properties as $prop): ?>
            <article
                class="property-card property-card-light js-prop-card"
                data-id="<?= (int) $prop['id'] ?>"
                data-type="<?= htmlspecialchars($prop['type'], ENT_QUOTES, 'UTF-8') ?>"
                data-operation="<?= htmlspecialchars($prop['operation'], ENT_QUOTES, 'UTF-8') ?>"
                data-price="<?= htmlspecialchars((string) ($prop['raw_price'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                data-location="<?= htmlspecialchars($prop['location'], ENT_QUOTES, 'UTF-8') ?>"
                data-title="<?= htmlspecialchars($prop['title'], ENT_QUOTES, 'UTF-8') ?>"
            >
                <div class="property-image">
                    <button
                        type="button"
                        class="property-card-media-btn"
                        onclick="handleMoreInfo(<?= (int) $prop['id'] ?>)"
                        aria-label="Ver ficha completa de <?= htmlspecialchars($prop['title'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <img
                            src="<?= htmlspecialchars($prop['image'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($prop['title'] . ' en ' . $prop['location'], ENT_QUOTES, 'UTF-8') ?>"
                            class="property-card-img"
                            loading="lazy"
                            decoding="async"
                            onerror="this.onerror=null;this.src='<?= BASE_URL ?>img/property-fallback.svg';"
                        >
                    </button>
                    <span class="property-tag"><?= htmlspecialchars($prop['operation_label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($prop['virtualTour']['url'])): ?>
                    <span class="property-tour-badge"><?= str_starts_with((string)($prop['virtualTour']['url']??''),'recorridos/') ? '🗺️ Recorrido interactivo' : '🕶️ Tour 360°' ?></span>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="favorite-btn"
                        data-prop-id="<?= (int) $prop['id'] ?>"
                        aria-pressed="false"
                        aria-label="Guardar <?= htmlspecialchars($prop['title'], ENT_QUOTES, 'UTF-8') ?> en favoritos"
                        onclick="toggleFavorite(<?= (int) $prop['id'] ?>, event)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>
                </div>
                <div class="property-body">
                    <div class="property-meta-top">
                        <span class="property-type-badge"><?= htmlspecialchars($prop['type_label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($prop['gallery']) && count($prop['gallery']) > 1): ?>
                        <span class="property-photos-count">📷 <?= count($prop['gallery']) ?> fotos</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="property-title">
                        <button type="button" class="property-title-btn" onclick="handleMoreInfo(<?= (int) $prop['id'] ?>)">
                            <?= htmlspecialchars($prop['title'], ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </h3>
                    <div class="property-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?= htmlspecialchars($prop['location'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if (!empty($prop['stats'])): ?>
                    <div class="property-stats">
                        <span><?= htmlspecialchars(implode(' · ', $prop['stats']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="property-card-footer">
                        <div class="property-price"><?= htmlspecialchars($prop['price'], ENT_QUOTES, 'UTF-8') ?></div>
                        <button type="button" class="property-detail-btn" onclick="handleMoreInfo(<?= (int) $prop['id'] ?>)">
                            Ver ficha →
                        </button>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div id="emptyCatalogState" class="catalog-empty-state js-empty-state" hidden>
            <p class="empty-title">No encontramos propiedades con esos criterios.</p>
            <p class="empty-sub">Ajusta o elimina alguna etiqueta de filtro para ver más resultados.</p>
            <button type="button" class="btn-dark" onclick="resetCatalogFilters()">Mostrar todas las propiedades</button>
        </div>
    </div>
</section>

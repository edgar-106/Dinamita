<?php
$uniqueLocations = [];
foreach ($properties ?? [] as $p) {
    $loc = trim((string) ($p['location'] ?? ''));
    if ($loc !== '' && !in_array($loc, $uniqueLocations, true)) {
        $uniqueLocations[] = $loc;
    }
}
sort($uniqueLocations);
?>
<!-- =========================================================
     SECCIÓN EXPLORAR / ADQUIRIR
========================================================= -->

<section class="section" id="adquirir">
    <div class="category-chooser" id="categoryChooser">
        <div class="chooser-header">
            <div>
                <div class="chooser-kicker">Explorar</div>
                <h2>¿Qué estás buscando?</h2>
            </div>
            <p>Selecciona el tipo de inmueble que necesitas y filtra por operación, ubicación, precio o favoritos sin recargar la página.</p>
        </div>
        <div class="chooser-grid">
            <button type="button" class="chooser-card" onclick="selectCategory('casa')" style="background-image:url('https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Casas</strong><small>Compra o renta</small></span>
            </button>
            <button type="button" class="chooser-card" onclick="selectCategory('departamento')" style="background-image:url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Departamentos</strong><small>Espacios modernos</small></span>
            </button>
            <button type="button" class="chooser-card" onclick="selectCategory('terreno')" style="background-image:url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Terrenos</strong><small>Invierte en tu futuro</small></span>
            </button>
        </div>
    </div>

    <div class="catalog-view" id="catalogView" hidden data-catalog-scope="home">
        <div class="catalog-topbar">
            <div>
                <span class="section-label">Catálogo en vivo</span>
                <h2 class="catalog-heading">Propiedades disponibles</h2>
            </div>
            <div class="catalog-topbar-actions">
                <button type="button" class="btn-catalog-back" onclick="openCategoryChooser()">← Elegir categoría visual</button>
                <a href="<?= BASE_URL ?>explorar" class="btn-catalog-full">Ver vista completa →</a>
            </div>
        </div>

        <!-- Barra de filtros interactivos sin recarga -->
        <div class="catalog-filters-bar" role="region" aria-label="Filtros del catálogo">
            <div class="filter-group">
                <span class="filter-group-label">Operación</span>
                <div class="filter-pills" role="group" aria-label="Filtrar por operación">
                    <button type="button" class="filter-pill js-filter-op" data-op="all">Todas</button>
                    <button type="button" class="filter-pill js-filter-op active" data-op="comprar">Comprar</button>
                    <button type="button" class="filter-pill js-filter-op" data-op="rentar">Rentar</button>
                </div>
            </div>

            <div class="filter-group">
                <span class="filter-group-label">Categoría</span>
                <div class="filter-pills" role="group" aria-label="Filtrar por categoría">
                    <button type="button" class="filter-pill js-filter-type active" data-type="all">Todas</button>
                    <button type="button" class="filter-pill js-filter-type" data-type="casa">Casas</button>
                    <button type="button" class="filter-pill js-filter-type" data-type="departamento">Departamentos</button>
                    <button type="button" class="filter-pill js-filter-type" data-type="terreno">Terrenos</button>
                </div>
            </div>

            <div class="filter-inputs-row">
                <div class="filter-field">
                    <label for="filterLocation">Ubicación o nombre</label>
                    <input
                        type="search"
                        id="filterLocation"
                        class="js-filter-location"
                        list="catalogLocationsList"
                        placeholder="Ej. Monterrey, Mérida, CDMX..."
                    >
                </div>
                <div class="filter-field">
                    <label for="filterMinPrice">Precio mín. (MXN)</label>
                    <input type="number" id="filterMinPrice" class="js-filter-min-price" min="0" step="10000" placeholder="0">
                </div>
                <div class="filter-field">
                    <label for="filterMaxPrice">Precio máx. (MXN)</label>
                    <input type="number" id="filterMaxPrice" class="js-filter-max-price" min="0" step="10000" placeholder="Sin límite">
                </div>
                <div class="filter-field">
                    <label for="filterSort">Ordenar por</label>
                    <select id="filterSort" class="js-filter-sort">
                        <option value="default">Destacadas</option>
                        <option value="price-asc">Menor precio</option>
                        <option value="price-desc">Mayor precio</option>
                    </select>
                </div>
                <div class="filter-field filter-field-fav">
                    <button type="button" class="filter-fav-toggle js-filter-fav" aria-pressed="false">
                        ♥ Favoritos (<span class="js-fav-count">0</span>)
                    </button>
                </div>
            </div>
        </div>

        <datalist id="catalogLocationsList">
            <?php foreach ($uniqueLocations as $loc): ?>
            <option value="<?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <!-- Etiquetas de filtros activos removibles + contador -->
        <div class="filter-status-bar">
            <div id="activeFilterChips" class="filter-chips-bar js-active-chips" role="region" aria-label="Filtros activos"></div>
            <div id="catalogResultCount" class="catalog-result-count js-result-count" aria-live="polite"></div>
        </div>

        <!-- Cuadrícula de propiedades con carga diferida y controles accesibles -->
        <div class="properties-grid" id="mainPropGrid">
            <?php foreach ($properties as $prop): ?>
            <article
                class="property-card js-prop-card"
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
                    <span class="property-tour-badge">🕶️ Tour 360°</span>
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
            <p class="empty-title">No encontramos propiedades con los filtros seleccionados.</p>
            <p class="empty-sub">Quita una etiqueta de filtro o restablece la búsqueda para ver todo el catálogo.</p>
            <button type="button" class="btn-dark" onclick="resetCatalogFilters()">Limpiar todos los filtros</button>
        </div>
    </div>
</section>

<!-- =========================================================
     ASESOR
========================================================= -->

<section class="section advisor-section" id="asesor">
    <div class="advisor-wrap">
        <div class="advisor-profile">
            <img
                class="advisor-photo"
                src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=300&q=80"
                alt="Foto de Lic. Sofía Valenzuela, asesora inmobiliaria"
                loading="lazy"
            >
            <div class="advisor-kicker">● ASESORA INMOBILIARIA</div>
            <h2 class="advisor-name">Lic. Sofía Valenzuela</h2>
            <div class="advisor-role">Especialista en orientación inmobiliaria</div>
            <p class="advisor-quote">"Te acompaño paso a paso en la revisión de información, validación de antecedentes y orientación del proceso inmobiliario."</p>
            <div class="advisor-actions">
                <button type="button" class="btn-advisor-dark" onclick="toggleChat()" aria-label="Abrir chat en vivo">▣ Chat en Vivo</button>
                <button type="button" class="btn-advisor-green" onclick="openInterestModal(null)" aria-label="Contactar asesor">▣ Contactar asesor</button>
            </div>
        </div>
        <div class="advisor-form">
            <h3>¿Deseas atención personalizada o agendar un recorrido guiado?</h3>
            <div class="small">Déjanos tus datos y te orientaremos sobre propiedades, requisitos y citas.</div>
            <form onsubmit="requestAdvisor(event)">
                <div class="advisor-form-grid">
                    <div>
                        <label for="advisorName" class="sr-only">Nombre completo</label>
                        <input id="advisorName" name="nombre" required placeholder="Nombre completo">
                    </div>
                    <div>
                        <label for="advisorPhone" class="sr-only">Teléfono o WhatsApp</label>
                        <input id="advisorPhone" name="telefono" type="tel" required placeholder="Teléfono / WhatsApp">
                    </div>
                    <div>
                        <label for="advisorTime" class="sr-only">Horario preferido</label>
                        <select id="advisorTime" name="horario_preferido">
                            <option>Inmediato (9:00 - 19:00)</option>
                            <option>Por la mañana</option>
                            <option>Por la tarde</option>
                        </select>
                    </div>
                </div>
                <div class="advisor-security">🔒 Tus datos se utilizarán para comunicación, seguimiento y citas relacionadas con el servicio.</div>
                <button class="advisor-submit" type="submit">SOLICITAR ASESORÍA GRATUITA</button>
            </form>
        </div>
    </div>
</section>

<!-- =========================================================
     INFORMACIÓN LEGAL
========================================================= -->

<section class="section legal" id="legal">
    <div class="legal-container">
        <div class="legal-header-block">
            <div class="legal-kicker">Certeza Jurídica</div>
            <h2>Términos y Legalidad</h2>
            <p>Información clara, resumida y directa para tu tranquilidad en cada operación.</p>
        </div>
        <div class="legal-accordion">
            <details class="legal-item">
                <summary class="legal-summary">Promesa de Compraventa</summary>
                <div class="legal-content"><p>Contrato preparatorio que asegura condiciones, precio y plazos antes de la escrituración formal ante notario.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Arrendamiento Seguro</summary>
                <div class="legal-content"><p>Requisitos transparentes de contratación: depósito en garantía, fiador con propiedad libre de gravamen o póliza jurídica.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Auditoría Registral (RPP)</summary>
                <div class="legal-content"><p>Revisión exhaustiva ante el Registro Público de la Propiedad para garantizar que cada inmueble esté libre de gravámenes.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Protección de Datos y Sesiones</summary>
                <div class="legal-content"><p>Tu información se transmite de forma protegida y se utiliza únicamente para dar seguimiento a tus citas o solicitudes de contacto.</p></div>
            </details>
        </div>
    </div>
</section>

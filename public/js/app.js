/* =========================================================
   app.js — INFONATEC Inmobiliaria
   Coordinador cliente: seguridad CSRF, red/offline, filtros y chips
   removibles, favoritos persistentes, galería real, gate de datos 360°,
   citas/contacto reales, chat IA SSE y panel administrativo.
========================================================= */

/* =========================================================
   UTILIDADES GLOBALES Y SEGURIDAD
========================================================= */

function getApiUrl(endpoint) {
    if (typeof window.APP_BASE_URL !== 'undefined' && window.APP_BASE_URL) {
        return window.APP_BASE_URL.replace(/\/+$/, '') + '/' + String(endpoint).replace(/^\/+/, '');
    }
    const pathname = window.location.pathname;
    const dir = pathname.substring(0, pathname.lastIndexOf('/') + 1);
    return dir + String(endpoint).replace(/^\/+/, '');
}

function getCsrfToken() {
    if (window.CSRF_TOKEN) return window.CSRF_TOKEN;
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
}

function apiHeaders(extra) {
    return Object.assign({
        'X-CSRF-Token': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest'
    }, extra || {});
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text == null ? '' : String(text);
    return div.innerHTML;
}

function normalizeSearchText(text) {
    return String(text || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function isSlowConnection() {
    if (!navigator.onLine) return true;
    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (!conn) return false;
    if (conn.saveData) return true;
    return conn.effectiveType === 'slow-2g' || conn.effectiveType === '2g' || conn.effectiveType === '3g';
}

/* =========================================================
   TOAST NOTIFICATIONS
========================================================= */

function showToast(message, type = '', duration = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast' + (type ? ' ' + type : '');
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(function () {
        toast.classList.add('hide');
        toast.addEventListener('animationend', function () {
            toast.remove();
        });
    }, duration);
}

/* =========================================================
   CATÁLOGO INDEXADO POR ID
========================================================= */

const propertiesById = {};
(function indexCatalog() {
    const raw = window.INFONATEC_PROPERTIES;
    if (Array.isArray(raw)) {
        raw.forEach(function (item) {
            if (item && item.id != null) {
                propertiesById[Number(item.id)] = item;
            }
        });
    } else if (raw && typeof raw === 'object') {
        Object.keys(raw).forEach(function (key) {
            const item = raw[key];
            if (item) {
                const pid = Number(item.id != null ? item.id : key);
                propertiesById[pid] = Object.assign({ id: pid }, item);
            }
        });
    }
})();

function getPropertyById(id) {
    return propertiesById[Number(id)] || null;
}

/* =========================================================
   INTRO SPLASH Y MONITOR DE RED
========================================================= */

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const splash = document.getElementById('intro-splash');
        if (splash) {
            document.body.style.overflow = 'hidden';
            const video = document.getElementById('intro-video');
            const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (video) {
                if (reduceMotion || isSlowConnection()) {
                    video.pause();
                    video.removeAttribute('autoplay');
                } else {
                    video.playbackRate = 1.0;
                    video.addEventListener('timeupdate', function () {
                        if (video.currentTime >= 7.4) {
                            video.currentTime = 0;
                            video.play().catch(function () {});
                        }
                    });
                }
            }
        }
    });

    window.closeSplash = function (operation) {
        const splash = document.getElementById('intro-splash');
        if (!splash) return;

        document.body.style.overflow = '';
        splash.classList.add('fade-out');

        let fired = false;
        function onFadeOut() {
            if (fired) return;
            fired = true;
            splash.style.display = 'none';
            splash.removeEventListener('transitionend', onFadeOut);
        }
        splash.addEventListener('transitionend', onFadeOut);
        setTimeout(onFadeOut, 1200);

        if (operation === 'vender') {
            setTimeout(function () {
                window.location.href = getApiUrl('vender');
            }, 100);
        } else if (operation === 'comprar') {
            setTimeout(function () {
                const categorias = document.getElementById('adquirir');
                if (categorias) {
                    categorias.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        }
    };

    window.openSplash = function (e) {
        if (e) e.preventDefault();
        const splash = document.getElementById('intro-splash');
        if (!splash) return;

        splash.style.display = 'flex';
        void splash.offsetWidth;
        splash.classList.remove('fade-out');
        document.body.style.overflow = 'hidden';

        const video = document.getElementById('intro-video');
        if (video && !isSlowConnection()) {
            video.currentTime = 0;
            video.play().catch(function () {});
        }
    };
})();

function updateNetworkBanner() {
    const banner = document.getElementById('networkStatusBanner');
    const iconEl = document.getElementById('networkStatusIcon');
    const textEl = document.getElementById('networkStatusText');
    if (!banner || !textEl) return;

    if (sessionStorage.getItem('infonatec_net_banner_dismissed') === '1' && navigator.onLine) {
        banner.hidden = true;
        return;
    }

    banner.classList.remove('is-offline', 'is-slow');

    if (!navigator.onLine) {
        banner.classList.add('is-offline');
        if (iconEl) iconEl.textContent = '⚠️';
        textEl.textContent = 'Sin conexión a internet. Estás en modo sin conexión: algunos recursos remotos o envíos no se guardarán hasta reconectar.';
        banner.hidden = false;
        return;
    }

    if (isSlowConnection()) {
        banner.classList.add('is-slow');
        if (iconEl) iconEl.textContent = '🐢';
        textEl.textContent = 'Conexión lenta o modo ahorro de datos detectado. Usa las galerías estáticas accesibles para evitar alto consumo en los tours 360°.';
        banner.hidden = false;
        return;
    }

    banner.hidden = true;
}

function dismissNetworkBanner() {
    sessionStorage.setItem('infonatec_net_banner_dismissed', '1');
    const banner = document.getElementById('networkStatusBanner');
    if (banner) banner.hidden = true;
}

window.addEventListener('online', function () {
    sessionStorage.removeItem('infonatec_net_banner_dismissed');
    updateNetworkBanner();
    showToast('Conexión restablecida.', 'success', 2500);
});

window.addEventListener('offline', function () {
    updateNetworkBanner();
    showToast('Se perdió la conexión a internet.', 'error', 4000);
});

/* =========================================================
   NAVBAR Y MENÚ MÓVIL
========================================================= */

window.addEventListener('scroll', function () {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileClose = document.getElementById('mobileMenuClose');

    if (hamburgerBtn && mobileMenu) {
        hamburgerBtn.addEventListener('click', function () {
            mobileMenu.classList.add('active');
            hamburgerBtn.setAttribute('aria-expanded', 'true');
        });
    }

    if (mobileClose && mobileMenu) {
        mobileClose.addEventListener('click', function () {
            mobileMenu.classList.remove('active');
            if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
        });
    }

    if (mobileMenu) {
        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                mobileMenu.classList.remove('active');
                if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
            });
        });
    }
});

/* =========================================================
   MODALES Y TECLADO GLOBAL
========================================================= */

function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    if (id === 'registerModal') {
        fillRegistrationLocation();
    } else if (id === 'interestModal') {
        prefillContactModal('interest');
    } else if (id === 'appointmentModal') {
        prefillContactModal('appointment');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('active');
    if (!document.querySelector('.modal.active')) {
        document.body.style.overflow = '';
    }
}

function fillRegistrationLocation() {
    const locationEl = document.getElementById('regLocation');
    if (!locationEl || locationEl.value || !navigator.geolocation) return;

    locationEl.placeholder = 'Autoriza tu ubicación para completar este campo';
    navigator.geolocation.getCurrentPosition(async function (position) {
        locationEl.placeholder = 'Detectando ciudad y estado…';
        try {
            const { latitude, longitude } = position.coords;
            const response = await fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + latitude + '&lon=' + longitude + '&zoom=10');
            if (!response.ok) throw new Error('No se pudo obtener la dirección');
            const data = await response.json();
            const address = data.address || {};
            const city = address.city || address.town || address.village || address.municipality || '';
            const state = address.state || '';
            locationEl.value = [city, state].filter(Boolean).join(', ');
            if (!locationEl.value) throw new Error('Ubicación no disponible');
        } catch (error) {
            locationEl.placeholder = 'Escribe tu ciudad y estado';
        }
    }, function () {
        locationEl.placeholder = 'Escribe tu ciudad y estado';
    }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
}

window.addEventListener('click', function (e) {
    document.querySelectorAll('.modal').forEach(function (modal) {
        if (e.target === modal) {
            if (modal.id === 'tourModal') {
                closeTourModal();
            } else {
                closeModal(modal.id);
            }
        }
    });
});

window.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const activeTour = document.querySelector('#tourModal.active');
        if (activeTour) {
            closeTourModal();
            return;
        }
        document.querySelectorAll('.modal.active').forEach(function (modal) {
            closeModal(modal.id);
        });
        return;
    }

    const activeTag = document.activeElement ? document.activeElement.tagName : '';
    if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
        return;
    }

    const propertyModalActive = document.querySelector('#propertyModal.active');
    if (propertyModalActive) {
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            nextGallerySlide();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            prevGallerySlide();
        } else if (e.key === 'Home') {
            e.preventDefault();
            goToGallerySlide(0);
        } else if (e.key === 'End') {
            e.preventDefault();
            goToGallerySlide(currentGalleryItems.length - 1);
        }
        return;
    }

    const tourModalActive = document.querySelector('#tourModal.active');
    const staticBox = document.getElementById('tourStaticGallery');
    if (tourModalActive && staticBox && staticBox.style.display !== 'none') {
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            nextStaticTourSlide();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            prevStaticTourSlide();
        }
    }
});

/* =========================================================
   FAVORITOS PERSISTENTES (BD + LOCALSTORAGE)
========================================================= */

const FAVORITES_STORAGE_KEY = 'infonatec_favorites';
let favoriteIds = new Set();

function loadInitialFavorites() {
    const set = new Set();
    if (window.INFONATEC_USER && Array.isArray(window.INFONATEC_FAVORITES)) {
        window.INFONATEC_FAVORITES.forEach(function (id) {
            if (Number(id) > 0) set.add(Number(id));
        });
    } else {
        try {
            const raw = localStorage.getItem(FAVORITES_STORAGE_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    parsed.forEach(function (id) {
                        if (Number(id) > 0) set.add(Number(id));
                    });
                }
            }
        } catch (e) {
            localStorage.removeItem(FAVORITES_STORAGE_KEY);
        }
    }
    favoriteIds = set;
    syncFavoriteUI();
}

function saveGuestFavorites() {
    try {
        localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(Array.from(favoriteIds)));
    } catch (e) {
        // Almacenamiento lleno o bloqueado
    }
}

function syncFavoriteUI() {
    document.querySelectorAll('.favorite-btn[data-prop-id]').forEach(function (btn) {
        const pid = Number(btn.getAttribute('data-prop-id'));
        const active = favoriteIds.has(pid);
        btn.classList.toggle('active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        const prop = getPropertyById(pid);
        const title = prop ? prop.title : 'propiedad';
        btn.setAttribute('aria-label', (active ? 'Quitar ' : 'Guardar ') + title + (active ? ' de favoritos' : ' en favoritos'));
    });

    document.querySelectorAll('.js-fav-count').forEach(function (el) {
        el.textContent = String(favoriteIds.size);
    });

    const detailFavBtn = document.getElementById('detailFavoriteBtn');
    if (detailFavBtn && currentPropertyId) {
        const isFav = favoriteIds.has(Number(currentPropertyId));
        detailFavBtn.classList.toggle('active', isFav);
        detailFavBtn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
        detailFavBtn.textContent = isFav ? '♥ En favoritos' : '♡ Guardar en favoritos';
    }
}

async function toggleFavorite(propertyId, event) {
    if (event) event.stopPropagation();
    const pid = Number(propertyId);
    if (!pid) return;

    const wasFav = favoriteIds.has(pid);
    if (wasFav) {
        favoriteIds.delete(pid);
    } else {
        favoriteIds.add(pid);
    }
    syncFavoriteUI();

    if (typeof applyCatalogFilters === 'function' && catalogState.onlyFavorites) {
        applyCatalogFilters();
    }

    if (window.INFONATEC_USER && window.INFONATEC_USER.id) {
        try {
            const response = await fetch(getApiUrl('api/favoritos/toggle'), {
                method: 'POST',
                headers: apiHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ id_propiedad: pid })
            });
            const data = await response.json();
            if (response.ok && data.success && Array.isArray(data.favorites)) {
                favoriteIds = new Set(data.favorites.map(Number));
                syncFavoriteUI();
                showToast(data.is_favorite ? 'Guardada en tus favoritos.' : 'Eliminada de favoritos.', 'info', 2200);
                return;
            }
        } catch (err) {
            console.warn('No se pudo sincronizar favorito con el servidor:', err);
        }
    }

    saveGuestFavorites();
    showToast(!wasFav ? 'Guardada en tus favoritos.' : 'Eliminada de favoritos.', 'info', 2200);
}

async function syncGuestFavoritesToServer() {
    try {
        const raw = localStorage.getItem(FAVORITES_STORAGE_KEY);
        if (!raw) return;
        const ids = JSON.parse(raw);
        if (!Array.isArray(ids) || ids.length === 0) return;

        await fetch(getApiUrl('api/favoritos/sync'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ favorites: ids })
        });
        localStorage.removeItem(FAVORITES_STORAGE_KEY);
    } catch (e) {
        // Ignorar fallo silencioso de sincronización
    }
}

/* =========================================================
   MOTOR DE FILTROS Y ETIQUETAS ACTIVAS REMOVIBLES
========================================================= */

const catalogState = {
    operation: 'all',
    type: 'all',
    location: '',
    minPrice: '',
    maxPrice: '',
    sort: 'default',
    onlyFavorites: false
};

const TYPE_LABELS = {
    all: 'Todas',
    casa: 'Casas',
    departamento: 'Departamentos',
    terreno: 'Terrenos'
};

const OP_LABELS = {
    all: 'Todas',
    comprar: 'Comprar',
    rentar: 'Rentar'
};

const EXPLORAR_HERO_TEXTS = {
    all: {
        title: 'Explorar Catálogo',
        desc: 'Encuentra casas, departamentos y terrenos verificados con galería completa y contacto directo.'
    },
    casa: {
        title: 'Casas',
        desc: 'Encuentra la casa de tus sueños, ideal para ti y tu familia.'
    },
    departamento: {
        title: 'Departamentos',
        desc: 'Espacios modernos y céntricos, adaptados a tu estilo de vida.'
    },
    terreno: {
        title: 'Terrenos',
        desc: 'Lotes residenciales, comerciales e industriales para construir tus proyectos.'
    }
};

let originalCardOrder = [];

function getCatalogContainer() {
    return document.getElementById('explorarCatalogView') || document.getElementById('catalogView');
}

function getCardsGrid() {
    return document.getElementById('propertyGrid') || document.getElementById('mainPropGrid');
}

function initCatalogFilters() {
    const scopeEl = getCatalogContainer();
    const grid = getCardsGrid();
    if (!scopeEl || !grid) return;

    originalCardOrder = Array.from(grid.querySelectorAll('.js-prop-card'));

    // Leer estado inicial desde atributos de Explorar (si vienen por query params)
    if (scopeEl.dataset.initOp) catalogState.operation = scopeEl.dataset.initOp;
    if (scopeEl.dataset.initType) catalogState.type = scopeEl.dataset.initType;
    if (scopeEl.dataset.initFav === '1') catalogState.onlyFavorites = true;

    const locInput = scopeEl.querySelector('.js-filter-location');
    const minInput = scopeEl.querySelector('.js-filter-min-price');
    const maxInput = scopeEl.querySelector('.js-filter-max-price');
    const sortSelect = scopeEl.querySelector('.js-filter-sort');
    const favToggle = scopeEl.querySelector('.js-filter-fav');

    if (locInput) {
        catalogState.location = locInput.value.trim();
        locInput.addEventListener('input', function () {
            catalogState.location = locInput.value.trim();
            applyCatalogFilters();
        });
    }
    if (minInput) {
        catalogState.minPrice = minInput.value.trim();
        minInput.addEventListener('input', function () {
            catalogState.minPrice = minInput.value.trim();
            applyCatalogFilters();
        });
    }
    if (maxInput) {
        catalogState.maxPrice = maxInput.value.trim();
        maxInput.addEventListener('input', function () {
            catalogState.maxPrice = maxInput.value.trim();
            applyCatalogFilters();
        });
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            catalogState.sort = sortSelect.value;
            applyCatalogFilters();
        });
    }
    if (favToggle) {
        favToggle.addEventListener('click', function () {
            catalogState.onlyFavorites = !catalogState.onlyFavorites;
            applyCatalogFilters();
        });
    }

    document.querySelectorAll('.js-filter-op').forEach(function (btn) {
        btn.addEventListener('click', function () {
            catalogState.operation = btn.getAttribute('data-op') || 'all';
            applyCatalogFilters();
        });
    });

    document.querySelectorAll('.js-filter-type').forEach(function (btn) {
        btn.addEventListener('click', function () {
            catalogState.type = btn.getAttribute('data-type') || 'all';
            applyCatalogFilters();
        });
    });

    applyCatalogFilters();
}

function syncCatalogControlsUI() {
    document.querySelectorAll('.js-filter-op').forEach(function (btn) {
        btn.classList.toggle('active', btn.getAttribute('data-op') === catalogState.operation);
    });
    document.querySelectorAll('.js-filter-type').forEach(function (btn) {
        btn.classList.toggle('active', btn.getAttribute('data-type') === catalogState.type);
    });

    // Sincronizar pestañas de operación en el navbar de Inicio
    const navOpComprar = document.getElementById('op-comprar');
    const navOpRentar = document.getElementById('op-rentar');
    if (navOpComprar) navOpComprar.classList.toggle('active', catalogState.operation === 'comprar');
    if (navOpRentar) navOpRentar.classList.toggle('active', catalogState.operation === 'rentar');

    const scopeEl = getCatalogContainer();
    if (scopeEl) {
        const locInput = scopeEl.querySelector('.js-filter-location');
        const minInput = scopeEl.querySelector('.js-filter-min-price');
        const maxInput = scopeEl.querySelector('.js-filter-max-price');
        const sortSelect = scopeEl.querySelector('.js-filter-sort');
        const favToggle = scopeEl.querySelector('.js-filter-fav');

        if (locInput && locInput.value !== catalogState.location) locInput.value = catalogState.location;
        if (minInput && minInput.value !== catalogState.minPrice) minInput.value = catalogState.minPrice;
        if (maxInput && maxInput.value !== catalogState.maxPrice) maxInput.value = catalogState.maxPrice;
        if (sortSelect && sortSelect.value !== catalogState.sort) sortSelect.value = catalogState.sort;
        if (favToggle) {
            favToggle.classList.toggle('active', catalogState.onlyFavorites);
            favToggle.setAttribute('aria-pressed', catalogState.onlyFavorites ? 'true' : 'false');
        }
    }

    // Actualizar título dinámico en /explorar
    const catTitle = document.getElementById('catTitle');
    const catDesc = document.getElementById('catDesc');
    if (catTitle && catDesc) {
        const meta = EXPLORAR_HERO_TEXTS[catalogState.type] || EXPLORAR_HERO_TEXTS.all;
        catTitle.textContent = meta.title;
        catDesc.textContent = meta.desc;
    }
}

function renderActiveFilterChips() {
    const scopeEl = getCatalogContainer();
    if (!scopeEl) return;
    const container = scopeEl.querySelector('.js-active-chips');
    if (!container) return;

    container.innerHTML = '';
    const chips = [];

    if (catalogState.operation !== 'all') {
        chips.push({
            key: 'operation',
            label: 'Operación: ' + (OP_LABELS[catalogState.operation] || catalogState.operation)
        });
    }
    if (catalogState.type !== 'all') {
        chips.push({
            key: 'type',
            label: 'Categoría: ' + (TYPE_LABELS[catalogState.type] || catalogState.type)
        });
    }
    if (catalogState.location !== '') {
        chips.push({
            key: 'location',
            label: 'Ubicación: "' + catalogState.location + '"'
        });
    }
    if (catalogState.minPrice !== '') {
        chips.push({
            key: 'minPrice',
            label: 'Desde $' + Number(catalogState.minPrice).toLocaleString('es-MX')
        });
    }
    if (catalogState.maxPrice !== '') {
        chips.push({
            key: 'maxPrice',
            label: 'Hasta $' + Number(catalogState.maxPrice).toLocaleString('es-MX')
        });
    }
    if (catalogState.onlyFavorites) {
        chips.push({
            key: 'onlyFavorites',
            label: 'Solo favoritos'
        });
    }
    if (catalogState.sort !== 'default') {
        chips.push({
            key: 'sort',
            label: catalogState.sort === 'price-asc' ? 'Orden: Menor precio' : 'Orden: Mayor precio'
        });
    }

    chips.forEach(function (chip) {
        const tag = document.createElement('span');
        tag.className = 'filter-chip';

        const textSpan = document.createElement('span');
        textSpan.textContent = chip.label;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'filter-chip-remove';
        btn.setAttribute('aria-label', 'Quitar filtro ' + chip.label);
        btn.textContent = '×';
        btn.addEventListener('click', function () {
            removeFilterByKey(chip.key);
        });

        tag.appendChild(textSpan);
        tag.appendChild(btn);
        container.appendChild(tag);
    });

    if (chips.length > 0) {
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'filter-clear-all';
        clearBtn.textContent = 'Limpiar todo';
        clearBtn.addEventListener('click', resetCatalogFilters);
        container.appendChild(clearBtn);
    }
}

function removeFilterByKey(key) {
    if (key === 'operation') catalogState.operation = 'all';
    else if (key === 'type') catalogState.type = 'all';
    else if (key === 'location') catalogState.location = '';
    else if (key === 'minPrice') catalogState.minPrice = '';
    else if (key === 'maxPrice') catalogState.maxPrice = '';
    else if (key === 'onlyFavorites') catalogState.onlyFavorites = false;
    else if (key === 'sort') catalogState.sort = 'default';

    applyCatalogFilters();
}

function resetCatalogFilters() {
    catalogState.operation = 'all';
    catalogState.type = 'all';
    catalogState.location = '';
    catalogState.minPrice = '';
    catalogState.maxPrice = '';
    catalogState.sort = 'default';
    catalogState.onlyFavorites = false;
    applyCatalogFilters();
}

function applyCatalogFilters() {
    const scopeEl = getCatalogContainer();
    const grid = getCardsGrid();
    if (!scopeEl || !grid) return;

    syncCatalogControlsUI();

    let cards = originalCardOrder.slice();
    if (catalogState.sort === 'price-asc') {
        cards.sort(function (a, b) {
            return Number(a.dataset.price || 0) - Number(b.dataset.price || 0);
        });
    } else if (catalogState.sort === 'price-desc') {
        cards.sort(function (a, b) {
            return Number(b.dataset.price || 0) - Number(a.dataset.price || 0);
        });
    }

    cards.forEach(function (card) {
        grid.appendChild(card);
    });

    const minVal = catalogState.minPrice !== '' ? Number(catalogState.minPrice) : null;
    const maxVal = catalogState.maxPrice !== '' ? Number(catalogState.maxPrice) : null;
    const locQuery = normalizeSearchText(catalogState.location);

    let visibleCount = 0;
    cards.forEach(function (card) {
        const pid = Number(card.dataset.id || 0);
        const price = Number(card.dataset.price || 0);
        const matchOp = catalogState.operation === 'all' || card.dataset.operation === catalogState.operation;
        const matchType = catalogState.type === 'all' || card.dataset.type === catalogState.type;
        const matchMin = minVal === null || isNaN(minVal) || price >= minVal;
        const matchMax = maxVal === null || isNaN(maxVal) || price <= maxVal;
        const matchFav = !catalogState.onlyFavorites || favoriteIds.has(pid);

        let matchLoc = true;
        if (locQuery !== '') {
            const haystack = normalizeSearchText((card.dataset.location || '') + ' ' + (card.dataset.title || ''));
            matchLoc = haystack.includes(locQuery);
        }

        const visible = matchOp && matchType && matchMin && matchMax && matchFav && matchLoc;
        card.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
    });

    const countEl = scopeEl.querySelector('.js-result-count');
    if (countEl) {
        countEl.textContent = 'Mostrando ' + visibleCount + ' de ' + cards.length + ' propiedades';
    }

    const emptyEl = scopeEl.querySelector('.js-empty-state');
    if (emptyEl) {
        emptyEl.hidden = visibleCount > 0;
    }

    renderActiveFilterChips();

    // Actualizar URL limpia sin recargar en /explorar
    if (scopeEl.id === 'explorarCatalogView' && window.history && window.history.replaceState) {
        const params = new URLSearchParams();
        if (catalogState.operation !== 'all') params.set('operacion', catalogState.operation);
        if (catalogState.type !== 'all') params.set('tipo', catalogState.type);
        if (catalogState.location !== '') params.set('ubicacion', catalogState.location);
        if (catalogState.minPrice !== '') params.set('min_precio', catalogState.minPrice);
        if (catalogState.maxPrice !== '') params.set('max_precio', catalogState.maxPrice);
        if (catalogState.onlyFavorites) params.set('favoritos', '1');

        const queryStr = params.toString();
        const nextUrl = window.location.pathname + (queryStr ? '?' + queryStr : '');
        window.history.replaceState(null, '', nextUrl);
    }
}

function buildExplorarUrl(paramsObj) {
    const baseUrl = getApiUrl('explorar');
    const params = new URLSearchParams();
    if (paramsObj && typeof paramsObj === 'object') {
        Object.keys(paramsObj).forEach(function (key) {
            const val = paramsObj[key];
            if (val != null && val !== '' && val !== 'all') {
                params.set(key, String(val));
            }
        });
    }
    const queryStr = params.toString();
    return queryStr ? baseUrl + '?' + queryStr : baseUrl;
}

// Funciones públicas de navegación hacia la vista completa (/explorar)
function openCategoryChooser() {
    const chooser = document.getElementById('categoryChooser');
    const section = document.getElementById('adquirir');
    if (chooser && section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }
    window.location.href = getApiUrl('#adquirir');
}

function selectCategory(type) {
    window.location.href = buildExplorarUrl({ tipo: type });
}

function openCatalogForOperation(operation) {
    window.location.href = buildExplorarUrl({ operacion: operation });
}

/* =========================================================
   FICHA COMPLETA + GALERÍA REAL DE IMÁGENES
========================================================= */

let currentPropertyId = null;
let currentGalleryItems = [];
let currentGalleryIndex = 0;

function handleMoreInfo(propertyId) {
    showProperty(propertyId);
}

function handleChatTour(propertyId) {
    showProperty(propertyId);
    openMediaTour('interactivo');
}

function renderPropertyGallery(property) {
    const fallbackUrl = getApiUrl('img/property-fallback.svg');
    const gallery = Array.isArray(property.gallery) && property.gallery.length > 0
        ? property.gallery
        : [{ url: property.image || fallbackUrl, alt: property.title, caption: property.title }];

    currentGalleryItems = gallery;
    currentGalleryIndex = 0;

    const prevBtn = document.getElementById('galleryPrevBtn');
    const nextBtn = document.getElementById('galleryNextBtn');
    const hasMultiple = gallery.length > 1;
    if (prevBtn) prevBtn.hidden = !hasMultiple;
    if (nextBtn) nextBtn.hidden = !hasMultiple;

    const fotosCount = document.getElementById('btnMediaFotosCount');
    if (fotosCount) fotosCount.textContent = String(gallery.length);

    const thumbsContainer = document.getElementById('galleryThumbnails');
    if (thumbsContainer) {
        thumbsContainer.innerHTML = '';
        thumbsContainer.hidden = !hasMultiple;

        gallery.forEach(function (item, idx) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gallery-thumb' + (idx === 0 ? ' active' : '');
            btn.setAttribute('aria-label', 'Ver fotografía ' + (idx + 1) + ' de ' + gallery.length + ': ' + (item.alt || property.title));

            const img = document.createElement('img');
            img.src = item.url || fallbackUrl;
            img.alt = item.alt || property.title;
            img.loading = 'lazy';
            img.onerror = function () {
                img.onerror = null;
                img.src = fallbackUrl;
            };

            btn.appendChild(img);
            btn.addEventListener('click', function () {
                goToGallerySlide(idx);
            });
            thumbsContainer.appendChild(btn);
        });
    }

    goToGallerySlide(0);
}

function goToGallerySlide(index) {
    if (!currentGalleryItems.length) return;
    const total = currentGalleryItems.length;
    currentGalleryIndex = ((index % total) + total) % total;

    const slide = currentGalleryItems[currentGalleryIndex];
    const mainImg = document.getElementById('detailImage');
    const counter = document.getElementById('galleryCounter');
    const caption = document.getElementById('galleryCaption');

    if (mainImg) {
        mainImg.src = slide.url;
        mainImg.alt = slide.alt || slide.caption || 'Fotografía del inmueble';
    }
    if (counter) {
        counter.textContent = (currentGalleryIndex + 1) + ' / ' + total;
    }
    if (caption) {
        caption.textContent = slide.caption || slide.alt || '';
    }

    document.querySelectorAll('#galleryThumbnails .gallery-thumb').forEach(function (thumb, idx) {
        thumb.classList.toggle('active', idx === currentGalleryIndex);
    });
}

function nextGallerySlide() {
    goToGallerySlide(currentGalleryIndex + 1);
}

function prevGallerySlide() {
    goToGallerySlide(currentGalleryIndex - 1);
}

// Swipe táctil para galería de ficha
document.addEventListener('DOMContentLoaded', function () {
    const stage = document.getElementById('galleryStage');
    if (!stage) return;
    let startX = 0;
    stage.addEventListener('touchstart', function (e) {
        if (e.changedTouches && e.changedTouches[0]) {
            startX = e.changedTouches[0].clientX;
        }
    }, { passive: true });
    stage.addEventListener('touchend', function (e) {
        if (!e.changedTouches || !e.changedTouches[0]) return;
        const diff = e.changedTouches[0].clientX - startX;
        if (Math.abs(diff) > 40) {
            if (diff < 0) nextGallerySlide();
            else prevGallerySlide();
        }
    }, { passive: true });
});

function renderPaymentGrid(property) {
    const container = document.getElementById('paymentOptions');
    if (!container) return;
    container.innerHTML = '';

    const options = Array.isArray(property.payment_options) && property.payment_options.length > 0
        ? property.payment_options
        : [{ code: 'CONTADO', label: 'Contado / SPEI', style: 'cash' }];

    options.forEach(function (opt) {
        const div = document.createElement('div');
        div.className = 'payment-option payment-' + (opt.style || 'cash');

        if (opt.style === 'mastercard') {
            div.innerHTML = '<span class="mastercard-symbol" aria-hidden="true"><i></i><i></i></span><span>' + escapeHtml(opt.label) + '</span>';
        } else {
            const symbol = opt.style === 'spei' ? '⇄' : (opt.style === 'visa' ? '🏦' : (opt.style === 'infonavit' || opt.style === 'fovissste' ? '🏛' : '$'));
            div.innerHTML = '<span class="payment-symbol" aria-hidden="true">' + symbol + '</span><span>' + escapeHtml(opt.label) + '</span>';
        }
        container.appendChild(div);
    });
}

function showProperty(id) {
    const pid = Number(id);
    currentPropertyId = pid;
    const property = getPropertyById(pid);

    if (!property) {
        showToast('No fue posible encontrar la información de esta propiedad.', 'error');
        return;
    }

    renderPropertyGallery(property);

    document.getElementById('detailTitle').textContent = property.title || 'Propiedad';
    document.getElementById('detailLocation').textContent = property.full_location || property.location || '';
    document.getElementById('detailPrice').textContent = property.price || '';

    const descEl = document.getElementById('detailDescription');
    if (descEl) {
        descEl.textContent = property.description || '';
        descEl.hidden = !property.description;
    }

    const basic = document.getElementById('basicDetails');
    basic.innerHTML = '';
    (property.details || []).forEach(function (item) {
        const li = document.createElement('li');
        li.textContent = item;
        basic.appendChild(li);
    });

    const operation = document.getElementById('operationDetails');
    operation.innerHTML = '';
    const opDetails = property.operation_details || [];
    (Array.isArray(opDetails) ? opDetails : [opDetails]).forEach(function (item) {
        const li = document.createElement('li');
        li.textContent = item;
        operation.appendChild(li);
    });

    const legalList = document.getElementById('legalDetails');
    if (legalList) {
        legalList.innerHTML = '';
        const legalItems = property.legal_details || ['Documentación en revisión por asesoría INFONATEC'];
        legalItems.forEach(function (item) {
            const li = document.createElement('li');
            li.textContent = item;
            legalList.appendChild(li);
        });
    }

    renderPaymentGrid(property);

    // Botones multimedia
    const btnVideo = document.getElementById('btnMediaVideo');
    const videoSrc = property.video || (property.virtualTour && property.virtualTour.video) || null;
    if (btnVideo) {
        btnVideo.hidden = !videoSrc;
    }

    const btnInteractivo = document.getElementById('btnMediaInteractivo');
    const hasTour = !!(property.virtualTour && property.virtualTour.url);
    const isSceneTour = !!(property.virtualTour && property.virtualTour.isSceneTour);
    if (btnInteractivo) {
        btnInteractivo.textContent = isSceneTour ? '🗺️ Recorrido por escenas' : '🕹️ Tour 360°';
        btnInteractivo.style.opacity = hasTour ? '1' : '0.55';
        btnInteractivo.title = hasTour
            ? (isSceneTour ? 'Ver recorrido interactivo por escenas' : 'Ver recorrido interactivo 360°')
            : 'Recorrido interactivo no disponible para esta propiedad';
    }

    const btnStatic360 = document.getElementById('btnMediaStatic360');
    if (btnStatic360) {
        btnStatic360.hidden = !(hasTour && Array.isArray(property.virtualTour.staticViews) && property.virtualTour.staticViews.length > 0);
    }

    syncFavoriteUI();
    openModal('propertyModal');
}

/* =========================================================
   RECORRIDO VIRTUAL 360° + GATE DE CONSUMO DE DATOS + GALERÍA ESTÁTICA
========================================================= */

let pendingTourProperty = null;
let staticTourSlides = [];
let staticTourIndex = 0;

function hideAllTourContainers() {
    const gate = document.getElementById('tourDataGate');
    const staticBox = document.getElementById('tourStaticGallery');
    const videoBox = document.getElementById('tourVideoContainer');
    const videoPlayer = document.getElementById('tourVideoPlayer');
    const iframeBox = document.getElementById('tourIframeContainer');
    const iframePlayer = document.getElementById('tourIframePlayer');
    const switchBtn = document.getElementById('btnSwitchToStaticInModal');

    if (gate) gate.style.display = 'none';
    if (staticBox) staticBox.style.display = 'none';
    if (videoBox) videoBox.style.display = 'none';
    if (iframeBox) iframeBox.style.display = 'none';
    if (switchBtn) switchBtn.style.display = 'none';

    if (videoPlayer) {
        videoPlayer.pause();
        videoPlayer.removeAttribute('src');
        videoPlayer.innerHTML = '';
    }
    if (iframePlayer) {
        iframePlayer.src = '';
    }
}

function buildStaticTourSlides(property) {
    if (property && property.virtualTour && Array.isArray(property.virtualTour.staticViews) && property.virtualTour.staticViews.length > 0) {
        return property.virtualTour.staticViews;
    }
    if (property && Array.isArray(property.gallery) && property.gallery.length > 0) {
        return property.gallery.map(function (g, i) {
            return {
                title: g.caption || (property.title + ' — Vista ' + (i + 1)),
                image: g.url,
                alt: g.alt || property.title,
                description: g.caption || property.description || ''
            };
        });
    }
    return [];
}

function openMediaTour(type) {
    const property = getPropertyById(currentPropertyId);

    if (type === 'fotos') {
        const galleryEl = document.getElementById('propertyGallery');
        const modalContent = document.querySelector('#propertyModal .modal-content');
        if (modalContent) modalContent.scrollTo({ top: 0, behavior: 'smooth' });
        if (galleryEl && currentGalleryItems.length > 1) {
            nextGallerySlide();
        }
        const nextBtn = document.getElementById('galleryNextBtn');
        if (nextBtn && !nextBtn.hidden) nextBtn.focus();
        return;
    }

    if (!property) {
        showToast('Selecciona una propiedad para ver sus medios.', 'info');
        return;
    }

    if ((type === 'interactivo' || type === 'static360') && !(property.virtualTour && property.virtualTour.url)) {
        showToast('Esta propiedad aún no cuenta con recorrido 360°.', 'info', 3800);
        return;
    }

    pendingTourProperty = property;

    const modalTitle = document.getElementById('tourModalTitle');
    const modalSubtitle = document.getElementById('tourModalSubtitle');
    const tourHelpText = document.getElementById('tourHelpText');

    if (modalTitle) {
        modalTitle.textContent = (property.virtualTour && property.virtualTour.title)
            ? property.virtualTour.title
            : (type === 'interactivo' ? 'Recorrido Interactivo 360°' : 'Recorrido de Propiedad');
    }
    if (modalSubtitle) {
        modalSubtitle.textContent = (property.virtualTour && property.virtualTour.subtitle)
            ? property.virtualTour.subtitle
            : property.title;
    }

    hideAllTourContainers();

    if (type === 'static360') {
        closeModal('propertyModal');
        openModal('tourModal');
        openStaticTourGallery();
        return;
    }

    if (type === 'video') {
        const videoRel = property.video || (property.virtualTour && property.virtualTour.video) || 'media/tour-propiedad.mp4';
        const videoFullUrl = /^(https?:)?\//i.test(videoRel) ? videoRel : getApiUrl(videoRel);
        const videoBox = document.getElementById('tourVideoContainer');
        const videoPlayer = document.getElementById('tourVideoPlayer');
        if (tourHelpText) tourHelpText.textContent = '💡 Disfruta del recorrido en video. Usa los controles para pausar o ver en pantalla completa.';
        if (videoBox) videoBox.style.display = 'block';
        if (videoPlayer) {
            videoPlayer.innerHTML = '<source src="' + escapeHtml(videoFullUrl) + '" type="video/mp4">Tu navegador no soporta video HTML5.';
            videoPlayer.load();
            videoPlayer.play().catch(function () {});
        }
        closeModal('propertyModal');
        openModal('tourModal');
        return;
    }

    // type === 'interactivo': los recorridos por escenas ligeros cargan directo; los 360° verifican gate de datos
    const isSceneTour = !!(property.virtualTour && property.virtualTour.isSceneTour);
    const preApproved = isSceneTour || (sessionStorage.getItem('infonatec_360_approved') === '1' && !isSlowConnection());
    closeModal('propertyModal');
    openModal('tourModal');

    if (preApproved) {
        loadInteractiveTourIframe(property);
    } else {
        showTourDataGate(property);
    }
}

function showTourDataGate(property) {
    hideAllTourContainers();
    const gate = document.getElementById('tourDataGate');
    const badge = document.getElementById('tourGateDataBadge');
    const slowWarn = document.getElementById('tourGateSlowWarning');
    const help = document.getElementById('tourHelpText');

    const estMb = (property && property.virtualTour && property.virtualTour.estimatedDataMb) || 4.8;
    if (badge) badge.textContent = 'Consumo estimado: ~' + estMb + ' MB';
    if (slowWarn) slowWarn.style.display = isSlowConnection() ? 'block' : 'none';
    if (help) help.textContent = '💡 Confirma si deseas descargar el recorrido 360° o utiliza la galería estática accesible.';
    if (gate) gate.style.display = 'block';
}

function confirmLoadInteractiveTour() {
    const remember = document.getElementById('tourGateRemember');
    if (remember && remember.checked) {
        sessionStorage.setItem('infonatec_360_approved', '1');
    }
    if (pendingTourProperty) {
        loadInteractiveTourIframe(pendingTourProperty);
    }
}

function loadInteractiveTourIframe(property) {
    hideAllTourContainers();

    const iframeBox = document.getElementById('tourIframeContainer');
    const iframePlayer = document.getElementById('tourIframePlayer');
    const switchBtn = document.getElementById('btnSwitchToStaticInModal');
    const help = document.getElementById('tourHelpText');

    let tourUrl = property.virtualTour && property.virtualTour.url ? property.virtualTour.url : 'tour';
    if (!/^https?:\/\//i.test(tourUrl) && !tourUrl.startsWith('/')) {
        tourUrl = getApiUrl(tourUrl);
    }
    const sep = tourUrl.includes('?') ? '&' : '?';
    const isSceneTour = !!(property.virtualTour && property.virtualTour.isSceneTour);
    const finalUrl = isSceneTour
        ? tourUrl + sep + 'embed=1'
        : tourUrl + sep + 'approved=1&prop=' + encodeURIComponent(property.id);

    if (iframeBox) iframeBox.style.display = 'block';
    if (iframePlayer) iframePlayer.src = finalUrl;
    if (switchBtn) switchBtn.style.display = isSceneTour ? 'none' : 'inline-block';
    if (help) {
        help.textContent = (property.virtualTour && property.virtualTour.helpText)
            ? property.virtualTour.helpText
            : '💡 Arrastra o usa las flechas y botones de zoom (+) / (−) dentro del recorrido.';
    }
}

function openStaticTourGallery() {
    const property = pendingTourProperty || getPropertyById(currentPropertyId);
    staticTourSlides = buildStaticTourSlides(property);
    staticTourIndex = 0;

    if (!staticTourSlides.length) {
        showToast('No hay vistas estáticas disponibles para este inmueble.', 'info');
        return;
    }

    hideAllTourContainers();
    const staticBox = document.getElementById('tourStaticGallery');
    const help = document.getElementById('tourHelpText');
    const thumbs = document.getElementById('staticTourThumbs');

    if (staticBox) staticBox.style.display = 'block';
    if (help) help.textContent = '💡 Modo accesible de bajo consumo: usa los botones o las flechas ← → para cambiar de vista.';

    if (thumbs) {
        thumbs.innerHTML = '';
        staticTourSlides.forEach(function (slide, idx) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gallery-thumb' + (idx === 0 ? ' active' : '');
            btn.setAttribute('aria-label', 'Ver escena ' + (idx + 1) + ': ' + slide.title);

            const img = document.createElement('img');
            img.src = slide.image;
            img.alt = slide.alt || slide.title;
            img.loading = 'lazy';
            btn.appendChild(img);
            btn.addEventListener('click', function () {
                goToStaticTourSlide(idx);
            });
            thumbs.appendChild(btn);
        });
    }

    goToStaticTourSlide(0);
}

function goToStaticTourSlide(index) {
    if (!staticTourSlides.length) return;
    const total = staticTourSlides.length;
    staticTourIndex = ((index % total) + total) % total;

    const slide = staticTourSlides[staticTourIndex];
    const img = document.getElementById('staticTourImage');
    const title = document.getElementById('staticTourTitle');
    const desc = document.getElementById('staticTourDesc');
    const counter = document.getElementById('staticTourCounter');

    if (img) {
        img.src = slide.image;
        img.alt = slide.alt || slide.title;
    }
    if (title) title.textContent = slide.title || 'Vista del recorrido';
    if (desc) desc.textContent = slide.description || '';
    if (counter) counter.textContent = (staticTourIndex + 1) + ' / ' + total;

    document.querySelectorAll('#staticTourThumbs .gallery-thumb').forEach(function (t, idx) {
        t.classList.toggle('active', idx === staticTourIndex);
    });
}

function prevStaticTourSlide() {
    goToStaticTourSlide(staticTourIndex - 1);
}

function nextStaticTourSlide() {
    goToStaticTourSlide(staticTourIndex + 1);
}

function switchStaticToInteractiveTour() {
    if (!pendingTourProperty) return;
    loadInteractiveTourIframe(pendingTourProperty);
}

function closeTourModal() {
    hideAllTourContainers();
    closeModal('tourModal');
    if (currentPropertyId) {
        openModal('propertyModal');
    }
}

// Comunicación segura desde iframe de tour hacia el modal padre
window.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin) return;
    if (event.data && event.data.type === 'infonatec-open-interest') {
        const propId = Number(event.data.propertyId || currentPropertyId || 6);
        hideAllTourContainers();
        closeModal('tourModal');
        openInterestModal(propId);
    }
});

/* =========================================================
   AUTENTICACIÓN (SESIÓN SEGURA + CSRF)
========================================================= */

async function register(event) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');

    const user = {
        name: (document.getElementById('regName') ? document.getElementById('regName').value : '').trim(),
        last: (document.getElementById('regLast') ? document.getElementById('regLast').value : '').trim(),
        email: (document.getElementById('regEmail') ? document.getElementById('regEmail').value : '').trim(),
        phone: (document.getElementById('regPhone') ? document.getElementById('regPhone').value : '').trim(),
        location: (document.getElementById('regLocation') ? document.getElementById('regLocation').value : '').trim(),
        password: document.getElementById('regPassword') ? document.getElementById('regPassword').value : ''
    };

    if (btn) btn.disabled = true;

    try {
        const formData = new URLSearchParams();
        Object.keys(user).forEach(function (k) {
            formData.append(k, user[k]);
        });

        const response = await fetch(getApiUrl('auth/register'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: formData.toString()
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            showToast(data.message || 'No fue posible completar el registro.', 'error');
            return;
        }

        if (data.csrf_token) window.CSRF_TOKEN = data.csrf_token;
        window.INFONATEC_USER = data.user || null;
        await syncGuestFavoritesToServer();

        closeModal('registerModal');
        showToast('¡Cuenta creada con éxito! Tus favoritos se sincronizaron.', 'success', 3000);

        setTimeout(function () {
            if (window.location.pathname.replace(/\/$/, '').endsWith('/vender')) location.reload();
            else window.location.href = getApiUrl('mis-propiedades');
        }, 900);
    } catch (error) {
        showToast('Error de conexión al registrar la cuenta.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

async function login(event) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');

    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (btn) btn.disabled = true;

    try {
        const formData = new URLSearchParams();
        formData.append('email', email);
        formData.append('password', password);

        const response = await fetch(getApiUrl('auth/login'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: formData.toString()
        });

        const data = await response.json();

        if (response.ok && data.success) {
            const user = data.user || {};
            if (data.csrf_token) window.CSRF_TOKEN = data.csrf_token;
            window.INFONATEC_USER = user;
            await syncGuestFavoritesToServer();

            closeModal('loginModal');
            showToast('¡Bienvenido, ' + (user.name || email.split('@')[0]) + '!', 'success');
            setTimeout(function () {
                if (user.is_admin) {
                    window.location.href = getApiUrl('admin');
                } else {
                    if (window.location.pathname.replace(/\/$/, '').endsWith('/vender')) location.reload();
                    else window.location.href = getApiUrl('mis-propiedades');
                }
            }, 800);
            return;
        }

        showToast(data.message || 'Correo o contraseña incorrectos.', 'error');
    } catch (error) {
        showToast('Error de conexión con el servidor.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

async function logoutUser() {
    try {
        await fetch(getApiUrl('auth/logout'), {
            method: 'POST',
            headers: apiHeaders({ 'Accept': 'application/json' })
        });
    } catch (e) {
        // Redirigir de cualquier forma
    }
    window.INFONATEC_USER = null;
    window.location.href = getApiUrl('');
}

/* =========================================================
   CONTACTO, INTERÉS Y CITAS REALES EN BD
========================================================= */

function prefillContactModal(kind) {
    const user = window.INFONATEC_USER;
    if (kind === 'interest') {
        const propInput = document.getElementById('interestPropertyId');
        const nameEl = document.getElementById('interestName');
        const phoneEl = document.getElementById('interestPhone');
        const emailEl = document.getElementById('interestEmail');
        const msgEl = document.getElementById('interestMsg');

        if (propInput) propInput.value = currentPropertyId ? String(currentPropertyId) : '';
        if (user) {
            if (nameEl && !nameEl.value) nameEl.value = user.name || '';
            if (phoneEl && !phoneEl.value) phoneEl.value = user.phone || '';
            if (emailEl && !emailEl.value) emailEl.value = user.email || '';
        }
        const prop = currentPropertyId ? getPropertyById(currentPropertyId) : null;
        if (msgEl && prop && !msgEl.value) {
            msgEl.value = 'Hola, me interesa recibir más información sobre "' + prop.title + '" (' + prop.location + ').';
        }
    } else if (kind === 'appointment') {
        const propInput = document.getElementById('apptPropertyId');
        const nameEl = document.getElementById('apptName');
        const emailEl = document.getElementById('apptEmail');
        const phoneEl = document.getElementById('apptPhone');
        const dateEl = document.getElementById('apptDate');

        if (propInput) propInput.value = currentPropertyId ? String(currentPropertyId) : '';
        if (dateEl) {
            const today = new Date().toISOString().split('T')[0];
            dateEl.min = today;
            if (!dateEl.value) dateEl.value = today;
        }
        if (user) {
            if (nameEl && !nameEl.value) nameEl.value = user.name || '';
            if (emailEl && !emailEl.value) emailEl.value = user.email || '';
            if (phoneEl && !phoneEl.value) phoneEl.value = user.phone || '';
        }
    }
}

function openInterestModal(propertyId) {
    if (propertyId) {
        currentPropertyId = Number(propertyId);
    }
    closeModal('propertyModal');
    openModal('interestModal');
}

function openAppointment() {
    closeModal('propertyModal');
    openModal('appointmentModal');
}

async function submitInterest(event) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    const payload = {
        tipo: 'INTERES',
        id_propiedad: document.getElementById('interestPropertyId')?.value || currentPropertyId || null,
        nombre: document.getElementById('interestName').value.trim(),
        telefono: document.getElementById('interestPhone').value.trim(),
        correo: document.getElementById('interestEmail').value.trim(),
        mensaje: document.getElementById('interestMsg').value.trim()
    };

    try {
        const response = await fetch(getApiUrl('api/contacto'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            showToast(data.message || 'No se pudo enviar la solicitud.', 'error');
            return;
        }
        closeModal('interestModal');
        form.reset();
        showToast(data.message || 'Solicitud enviada correctamente.', 'success', 4000);
    } catch (error) {
        showToast('Error de conexión al enviar tu solicitud.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

function sendInterest(event) {
    return submitInterest(event);
}

async function scheduleAppointment(event) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    const propId = Number(document.getElementById('apptPropertyId')?.value || currentPropertyId || 0);
    const payload = {
        id_propiedad: propId,
        nombre: document.getElementById('apptName').value.trim(),
        correo: document.getElementById('apptEmail').value.trim(),
        fecha: document.getElementById('apptDate').value,
        hora: document.getElementById('apptTime').value,
        telefono: document.getElementById('apptPhone').value.trim(),
        medio_contacto: document.getElementById('apptContact').value,
        observaciones: (document.getElementById('apptNotes')?.value || '').trim()
    };

    try {
        const response = await fetch(getApiUrl('api/citas'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            showToast(data.message || 'No fue posible agendar la cita.', 'error', 4200);
            return;
        }
        closeModal('appointmentModal');
        form.reset();
        showToast(data.message || 'Cita registrada correctamente.', 'success', 4500);
    } catch (error) {
        showToast('Error de conexión al agendar la cita.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

async function requestAdvisor(event) {
    event.preventDefault();
    const form = event.target;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    const payload = {
        tipo: 'ASESORIA',
        nombre: document.getElementById('advisorName').value.trim(),
        telefono: document.getElementById('advisorPhone').value.trim(),
        horario_preferido: document.getElementById('advisorTime').value,
        mensaje: 'Solicitud de asesoría personalizada desde página principal.'
    };

    try {
        const response = await fetch(getApiUrl('api/contacto'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            showToast(data.message || 'No se pudo enviar la solicitud.', 'error');
            return;
        }
        form.reset();
        showToast(data.message || 'Solicitud de asesoría enviada.', 'success', 4000);
    } catch (error) {
        showToast('Error de conexión al enviar la solicitud.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

/* =========================================================
   CHAT Y ASISTENTE VIRTUAL CON IA (SSE + CSRF)
========================================================= */

function toggleChat() {
    const chatBox = document.getElementById('chatBox');
    if (!chatBox) return;
    chatBox.classList.toggle('active');

    if (chatBox.classList.contains('active')) {
        const messages = document.getElementById('chatMessages');
        if (messages) messages.scrollTop = messages.scrollHeight;
        const input = document.getElementById('chatInput');
        if (input) input.focus();
    }
}

function formatBotText(text) {
    const safe = escapeHtml(text);
    return safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
}

function sendQuickQuestion(questionText) {
    const input = document.getElementById('chatInput');
    if (input) {
        input.value = questionText;
        sendMessage();
    }
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    if (!input) return;
    const text = input.value.trim();
    if (text === '') return;

    const messages = document.getElementById('chatMessages');
    if (!messages) return;

    const userDiv = document.createElement('div');
    userDiv.className = 'message user';
    userDiv.textContent = text;
    messages.appendChild(userDiv);

    input.value = '';
    messages.scrollTop = messages.scrollHeight;

    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.id = 'chatTypingIndicator';
    typingDiv.innerHTML = '<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>';
    messages.appendChild(typingDiv);
    messages.scrollTop = messages.scrollHeight;

    try {
        const response = await fetch(getApiUrl('api/ai/chat/stream'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ message: text })
        });

        if (typingDiv.parentNode) typingDiv.remove();

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const botDiv = document.createElement('div');
        botDiv.className = 'message';
        messages.appendChild(botDiv);

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let fullText = '';
        let meta = { engine: 'local_expert', related: [] };

        const processEventBlock = function (block) {
            const lines = block.split('\n');
            let eventName = '';
            let dataStr = '';
            for (const line of lines) {
                if (line.startsWith('event:')) {
                    eventName = line.slice(6).trim();
                } else if (line.startsWith('data:')) {
                    dataStr = line.slice(5).trim();
                }
            }
            if (!dataStr) return;
            const data = JSON.parse(dataStr);
            if (eventName === 'meta') {
                meta = data;
            } else if (eventName === 'token') {
                fullText += data.token || '';
                botDiv.innerHTML = formatBotText(fullText);
            }
        };

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            const blocks = buffer.split('\n\n');
            buffer = blocks.pop() || '';
            blocks.forEach(function (block) {
                const cleanBlock = block.trim();
                if (cleanBlock) processEventBlock(cleanBlock);
            });
        }
        if (buffer.trim()) processEventBlock(buffer.trim());

        const related = meta.related;
        if (Array.isArray(related) && related.length > 0) {
            related.forEach(function (propId) {
                const prop = getPropertyById(propId);
                if (!prop) return;

                const card = document.createElement('div');
                card.className = 'chat-prop-card';
                const tourBtnHtml = (prop.virtualTour && prop.virtualTour.url)
                    ? '<button type="button" class="chat-prop-btn chat-prop-btn-tour" onclick="handleChatTour(' + Number(prop.id) + ')">🕶️ Recorrido 360°</button>'
                    : '';

                card.innerHTML =
                    '<div><strong>' + escapeHtml(prop.title) + '</strong><br>' +
                    '<span style="color:#9e7a37;font-weight:700;">' + escapeHtml(prop.price) + '</span> · ' +
                    '<span style="color:#475569;">' + escapeHtml(prop.location) + '</span></div>' +
                    '<div class="chat-prop-actions">' +
                    '<button type="button" class="chat-prop-btn" onclick="handleMoreInfo(' + Number(prop.id) + ')">Ver Ficha</button>' +
                    tourBtnHtml +
                    '</div>';
                messages.appendChild(card);
            });
        }

        messages.scrollTop = messages.scrollHeight;
    } catch (error) {
        if (typingDiv.parentNode) typingDiv.remove();
        const botDiv = document.createElement('div');
        botDiv.className = 'message';
        botDiv.textContent = 'No pude conectar con el asistente en este momento. Puedes revisar las fichas del catálogo o escribirnos desde la sección Asesor.';
        messages.appendChild(botDiv);
        messages.scrollTop = messages.scrollHeight;
    }
}

/* =========================================================
   REDACCIÓN CON IA EN VENDER
========================================================= */

async function generateAIDescription(btnElement) {
    const titleInput = document.getElementById('pubTitle');
    const opInput = document.getElementById('pubOperation');
    const priceInput = document.getElementById('pubPrice');
    const locInput = document.getElementById('pubLocation');
    const cityInput = document.getElementById('pubCity');
    const stateInput = document.getElementById('pubState');
    const descArea = document.getElementById('pubDesc');
    const roomsInput = document.getElementById('pubBedrooms');
    const bathsInput = document.getElementById('pubBathrooms');
    const areaInput = (typeof window.selectedType !== 'undefined' && window.selectedType === 'terreno')
        ? document.getElementById('pubLandArea')
        : document.getElementById('pubArea');

    if (!descArea) {
        showToast('No se encontró el campo de descripción.', 'error');
        return;
    }

    const originalText = btnElement ? btnElement.innerHTML : '';
    if (btnElement) {
        btnElement.disabled = true;
        btnElement.innerHTML = '✨ Redactando con IA...';
    }

    const activeType = (typeof window.selectedType !== 'undefined' && window.selectedType)
        ? window.selectedType
        : 'casa';

    const payload = {
        title: titleInput ? titleInput.value.trim() : '',
        type: activeType,
        operation: opInput ? opInput.value : 'VENTA',
        price: priceInput ? priceInput.value.trim() : '',
        location: locInput ? locInput.value.trim() : '',
        city: cityInput ? cityInput.value.trim() : '',
        state: stateInput ? stateInput.value.trim() : '',
        bedrooms: roomsInput ? roomsInput.value.trim() : '',
        bathrooms: bathsInput ? bathsInput.value.trim() : '',
        area: areaInput ? areaInput.value.trim() : '',
        details: descArea.value.trim()
    };

    try {
        const res = await fetch(getApiUrl('api/ai/generate-description'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok && data && data.description) {
            descArea.value = data.description;
            if (typeof window.onAIDescriptionGenerated === 'function') {
                window.onAIDescriptionGenerated(data.description, data.engine || 'local_template');
            }
            showToast('Borrador generado con IA. Revísalo y apruébalo antes de publicar.', 'info', 4000);
        } else {
            showToast(data.message || 'No fue posible generar la descripción automática.', 'error');
        }
    } catch (e) {
        showToast('Error de conexión con el asistente de redacción.', 'error');
    } finally {
        if (btnElement) {
            btnElement.disabled = false;
            btnElement.innerHTML = originalText;
        }
    }
}

/* =========================================================
   ADMINISTRACIÓN (CSRF EN TODAS LAS ACCIONES POST)
========================================================= */

async function adminCambiarEstado(citaId, estado) {
    try {
        const response = await fetch(getApiUrl('admin/reservas/' + citaId + '/estado'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: 'estado=' + encodeURIComponent(estado)
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showToast('Reserva #' + citaId + ' actualizada a: ' + estado, 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        } else {
            showToast(data.message || 'No se pudo actualizar la reserva.', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al actualizar la reserva.', 'error');
    }
}

async function adminConfirmarPago(transaccionId) {
    try {
        const response = await fetch(getApiUrl('admin/pagos/' + transaccionId + '/confirmar'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' })
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showToast('Pago confirmado correctamente. Propiedad liberada.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        } else {
            showToast(data.message || 'No se pudo confirmar el pago.', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al confirmar el pago.', 'error');
    }
}

async function adminRegistrarTour(event) {
    event.preventDefault();
    const form = event.target;
    try {
        const formData = new URLSearchParams();
        formData.append('id_propiedad', form.id_propiedad.value);
        formData.append('url_3d', form.url_3d.value);
        formData.append('descripcion', form.descripcion.value);

        const response = await fetch(getApiUrl('admin/eventos/tour'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: formData.toString()
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showToast('Tour registrado exitosamente.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        } else {
            showToast(data.message || 'No se pudo registrar el tour.', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al registrar el tour.', 'error');
    }
}

async function adminEliminarTour(paseoId) {
    try {
        const response = await fetch(getApiUrl('admin/eventos/tour/' + paseoId + '/eliminar'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' })
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showToast('Tour eliminado correctamente.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        } else {
            showToast(data.message || 'No se pudo eliminar el tour.', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al eliminar el tour.', 'error');
    }
}

async function adminCrearVehiculo(event) {
    event.preventDefault();
    const form = event.target;
    try {
        const formData = new URLSearchParams();
        formData.append('placa', form.placa.value);
        formData.append('tipo', form.tipo.value);
        formData.append('marca', form.marca.value);
        formData.append('modelo', form.modelo.value);
        formData.append('capacidad', form.capacidad.value);
        formData.append('estado', form.estado.value);
        formData.append('observaciones', form.observaciones.value);

        const response = await fetch(getApiUrl('admin/logistica/vehiculo'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: formData.toString()
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showToast('Vehículo registrado exitosamente.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        } else {
            showToast(data.message || 'No se pudo registrar el vehículo.', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al registrar el vehículo.', 'error');
    }
}

async function adminCambiarEstadoVehiculo(vehiculoId, estado) {
    try {
        const response = await fetch(getApiUrl('admin/logistica/vehiculo/' + vehiculoId + '/estado'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: 'estado=' + encodeURIComponent(estado)
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showToast('Vehículo actualizado correctamente.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        } else {
            showToast(data.message || 'No se pudo actualizar el vehículo.', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al actualizar el vehículo.', 'error');
    }
}

async function adminAsignarTransporte(event) {
    event.preventDefault();
    const form = event.target;
    try {
        const formData = new URLSearchParams();
        formData.append('id_propiedad', form.id_propiedad.value);
        formData.append('id_vehiculo', form.id_vehiculo.value);
        formData.append('id_cita', form.id_cita ? form.id_cita.value : '');
        formData.append('fecha_salida', form.fecha_salida ? form.fecha_salida.value.replace('T', ' ') : '');
        formData.append('fecha_llegada', form.fecha_llegada ? form.fecha_llegada.value.replace('T', ' ') : '');
        formData.append('estado', form.estado.value);
        formData.append('observaciones', form.observaciones.value);

        const response = await fetch(getApiUrl('admin/logistica/asignar'), {
            method: 'POST',
            headers: apiHeaders({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: formData.toString()
        });
        const data = await response.json();
        if (response.ok && data.success) {
            showToast('Transporte asignado exitosamente.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        } else {
            showToast(data.message || 'No se pudo asignar el transporte.', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al asignar transporte.', 'error');
    }
}

/* =========================================================
   INICIALIZACIÓN PRINCIPAL AL CARGAR DOM
========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    // Eliminar cualquier rastro heredado de localStorage de autenticación
    localStorage.removeItem('br_usuario');

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logoutUser);
    }

    updateNetworkBanner();
    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (conn && typeof conn.addEventListener === 'function') {
        conn.addEventListener('change', updateNetworkBanner);
    }

    loadInitialFavorites();
    initCatalogFilters();

    // Si la URL incluye ?categoria= en Home, redirigir al catálogo completo filtrado
    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get('categoria');
    if (categoryParam && document.body.classList.contains('page-home')) {
        selectCategory(categoryParam);
    }
});

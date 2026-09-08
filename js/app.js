/* =========================================================
   app.js — NIDUS Inmobiliaria
   JavaScript extraído de index.html
========================================================= */

/* =========================================================
   INTRO SPLASH
   - Acelera el video a 4× para que dure ~3 seg.
   - Un timeout de seguridad cierra el splash a los 3 seg.
     aunque el video no haya terminado o falle.
========================================================= */

(function () {
    // Bloquear scroll mientras el splash esté activo y configurar videos
    document.addEventListener('DOMContentLoaded', function () {
        const splash = document.getElementById('intro-splash');
        if (splash) {
            document.body.style.overflow = 'hidden';
            
            // Loop the intro video cleanly at 7.4s
            const video = document.getElementById('intro-video');
            if (video) {
                video.playbackRate = 1.0;
                video.addEventListener('timeupdate', function () {
                    if (video.currentTime >= 7.4) {
                        video.currentTime = 0;
                        video.play();
                    }
                });
            }
        }

        // Configurar los videos de las propiedades: solo reproducir en hover, loop a los 6 segundos
        const propertyCards = document.querySelectorAll('.property-card');
        propertyCards.forEach(function(card) {
            const vid = card.querySelector('.property-video');
            if (vid) {
                // Pausar al cargar por si acaso
                vid.pause();
                
                // Eventos de hover en la tarjeta
                card.addEventListener('mouseenter', function() {
                    vid.play().catch(function(e) { console.log('Autoplay impedido en hover', e); });
                });
                
                card.addEventListener('mouseleave', function() {
                    vid.pause();
                    vid.currentTime = 0; // Regresar al inicio al quitar el cursor
                });

                // Loop a los 6 segundos
                vid.addEventListener('timeupdate', function () {
                    if (vid.currentTime >= 6) {
                        vid.currentTime = 0;
                        vid.play();
                    }
                });
            }
        });
    });

    // Función global para cerrar el splash
    window.closeSplash = function(operation) {
        const splash = document.getElementById('intro-splash');
        if (!splash) return;

        document.body.style.overflow = '';
        splash.classList.add('fade-out');

        function onFadeOut() {
            splash.style.display = 'none';
            splash.removeEventListener('transitionend', onFadeOut);
        }
        splash.addEventListener('transitionend', onFadeOut);

        // Si se seleccionó una operación
        if (operation === 'vender') {
            // Esperar un poco a que el splash se desvanezca y abrir modal de publicar
            setTimeout(function() {
                if (typeof openPublishModal === 'function') {
                    openPublishModal();
                }
            }, 500);
        } else if (operation) {
            // Filtrar propiedades (ej. 'comprar')
            setTimeout(function() {
                if (typeof filterOperation === 'function') {
                    filterOperation(operation);
                }
            }, 100);
        }
    };

    // Función global para abrir el splash de nuevo
    window.openSplash = function(e) {
        if(e) e.preventDefault();
        const splash = document.getElementById('intro-splash');
        if (!splash) return;

        splash.style.display = 'flex';
        // Forzar reflow para reiniciar la transición
        void splash.offsetWidth;
        splash.classList.remove('fade-out');
        document.body.style.overflow = 'hidden';

        const video = document.getElementById('intro-video');
        if (video) {
            video.currentTime = 0;
            video.play();
        }
    };
})();


/* =========================================================
   TOAST NOTIFICATIONS
========================================================= */

/**
 * Muestra una notificación tipo toast en pantalla.
 * @param {string} message - Texto del mensaje.
 * @param {'success'|'error'|'info'|''} type - Estilo del toast.
 * @param {number} duration - Duración en ms (default 3500).
 */
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
   NAVBAR — scroll effect
========================================================= */

window.addEventListener('scroll', function () {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

/* =========================================================
   MENÚ HAMBURGUESA
========================================================= */

const hamburgerBtn  = document.getElementById('hamburgerBtn');
const mobileMenu    = document.getElementById('mobileMenu');
const mobileClose   = document.getElementById('mobileMenuClose');

if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', function () {
        mobileMenu.classList.add('active');
        hamburgerBtn.setAttribute('aria-expanded', 'true');
    });
}

if (mobileClose) {
    mobileClose.addEventListener('click', function () {
        mobileMenu.classList.remove('active');
        if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
    });
}

// Cerrar menú móvil al hacer clic en un enlace
if (mobileMenu) {
    mobileMenu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            mobileMenu.classList.remove('active');
            if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
        });
    });
}

/* =========================================================
   MODALES
========================================================= */

function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Cerrar modal al hacer clic en el fondo oscuro
window.addEventListener('click', function (e) {
    document.querySelectorAll('.modal').forEach(function (modal) {
        if (e.target === modal) {
            if (modal.id === 'tourModal') {
                closeTourModal();
            } else {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
});

// Cerrar modal con tecla Escape
window.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const activeTour = document.querySelector('#tourModal.active');
        if (activeTour) {
            closeTourModal();
        } else {
            document.querySelectorAll('.modal.active').forEach(function (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    }
});

/* =========================================================
   PROPIEDADES — datos
========================================================= */

const properties = {
    1: {
        title: 'Lote Residencial Montebello',
        type: 'Terreno',
        location: 'Mérida, Yucatán',
        image: 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1400&q=90',
        price: '$1,850,000',
        details: [
            '450 m² de superficie',
            'Terreno regular',
            'Servicios disponibles',
            'Acceso vehicular',
            'Zona residencial'
        ],
        operation: [
            'Venta',
            'Revisión de situación legal',
            'Efectivo',
            'Financiamiento sujeto a condiciones'
        ]
    },
    2: {
        title: 'Residencia Minimalista Las Cumbres',
        type: 'Casa',
        location: 'Monterrey, Nuevo León',
        image: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=90',
        price: '$6,800,000',
        details: [
            '4 habitaciones',
            '5 baños',
            '340 m² de construcción',
            'Alberca',
            'Estacionamiento'
        ],
        operation: [
            'Venta',
            'Efectivo',
            'Crédito bancario',
            'Financiamiento sujeto a condiciones'
        ],
        virtualTour: {
            type: 'iframe',
            url: 'img/index.html',
            title: 'Recorrido Interactivo 3D — Residencia Minimalista Las Cumbres',
            subtitle: 'Haz scroll vertical dentro del recorrido para desplazarte y explorar fluidamente los espacios.',
            buttonText: 'VER RECORRIDO INTERACTIVO 3D ▶',
            helpText: '💡 Desplázate (haz scroll con la rueda del ratón o desliza el dedo) para avanzar o retroceder en el recorrido.'
        }
    },
    3: {
        title: 'Penthouse Polanco Sky View',
        type: 'Departamento',
        location: 'Ciudad de México',
        image: 'https://images.unsplash.com/photo-1600607688969-a5bfcd646154?auto=format&fit=crop&w=1400&q=90',
        price: '$38,000 / mes',
        details: [
            '160 m²',
            '3 habitaciones',
            '2.5 baños',
            'Estacionamiento',
            'Seguridad'
        ],
        operation: [
            'Renta',
            'Primer mes',
            'Depósito requerido',
            'Póliza jurídica'
        ],
        virtualTour: {
            video: 'casa 1.mp4',
            title: 'Recorrido Virtual — Penthouse Polanco Sky View',
            subtitle: 'Conoce los espacios interiores, acabados y vista panorámica de este exclusivo penthouse.'
        }
    },
    4: {
        title: 'Departamento Urbano Providencia',
        type: 'Departamento',
        location: 'Guadalajara, Jalisco',
        image: 'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1400&q=90',
        price: '$14,500 / mes',
        details: [
            '78 m²',
            '2 recámaras',
            '1 baño',
            '1 cajón',
            'Zona urbana'
        ],
        operation: [
            'Renta',
            'Primer mes',
            'Depósito',
            'Requisitos de arrendamiento'
        ]
    },
    5: {
        title: 'Lote Campestre Valle de Bravo',
        type: 'Terreno',
        location: 'Valle de Bravo, Estado de México',
        image: 'https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1400&q=90',
        price: '$950,000',
        details: [
            '1,200 m²',
            'Terreno campestre',
            'Servicios a pie de lote',
            'Acceso',
            'Zona natural'
        ],
        operation: [
            'Venta',
            'Revisión legal',
            'Efectivo',
            'Financiamiento sujeto a condiciones'
        ]
    }
};

let currentPropertyId = null;

function showProperty(id) {
    currentPropertyId = id;
    const property = properties[id];

    document.getElementById('detailImage').src = property.image;
    document.getElementById('detailImage').alt = property.title;
    document.getElementById('detailTitle').innerText = property.title;
    document.getElementById('detailLocation').innerText =
        property.location + ' · ' + property.price;

    // Detalles básicos
    const basic = document.getElementById('basicDetails');
    basic.innerHTML = '';
    property.details.forEach(function (item) {
        const li = document.createElement('li');
        li.textContent = item;
        basic.appendChild(li);
    });

    // Detalles de operación
    const operation = document.getElementById('operationDetails');
    operation.innerHTML = '';
    property.operation.forEach(function (item) {
        const li = document.createElement('li');
        li.textContent = item;
        operation.appendChild(li);
    });

    // Personalizar sección de recorrido virtual
    const mediaSection = document.getElementById('mediaGallerySection');
    if (mediaSection) {
        mediaSection.style.display = 'block'; // Always show since all have at least a photo
    }

    // Mostrar información según sesión
    const logged = localStorage.getItem('br_usuario');
    document.getElementById('fullInformation').style.display  = logged ? 'block' : 'none';
    document.getElementById('loginRequired').style.display    = logged ? 'none'  : 'block';

    openModal('propertyModal');
}

/* =========================================================
   RECORRIDO VIRTUAL (Video o Interactivo)
========================================================= */

function openMediaTour(type) {
    if (type === 'fotos') {
        // En un futuro se podría abrir una galería, por ahora indicamos que están arriba
        document.querySelector('#propertyModal .modal-content').scrollTo({top: 0, behavior: 'smooth'});
        showToast('Explora las fotografías en la parte superior.', 'info');
        return;
    }

    const propertyId = currentPropertyId || 3;
    const property = properties[propertyId];
    
    // Fallback content in case they don't have specific virtual tours defined yet
    let tourUrl = 'https://my.matterport.com/show/?m=J2b34X5T5zM'; // Default interactive
    let videoUrl = 'casa 1.mp4'; // Default video

    if (property && property.virtualTour) {
        if (property.virtualTour.url) tourUrl = property.virtualTour.url;
        if (property.virtualTour.video) videoUrl = property.virtualTour.video;
    }

    const modalTitle = document.getElementById('tourModalTitle');
    const modalSubtitle = document.getElementById('tourModalSubtitle');
    const tourHelpText = document.getElementById('tourHelpText');
    const videoContainer = document.getElementById('tourVideoContainer');
    const videoPlayer = document.getElementById('tourVideoPlayer');
    const iframeContainer = document.getElementById('tourIframeContainer');
    const iframePlayer = document.getElementById('tourIframePlayer');

    if (modalTitle) {
        modalTitle.innerText = type === 'interactivo' ? 'Recorrido Interactivo' : 'Recorrido en Video';
    }
    if (modalSubtitle) modalSubtitle.innerText = property ? property.title : 'Explorando propiedad';
    if (tourHelpText) {
        tourHelpText.innerText = type === 'interactivo' 
            ? '💡 Interactúa con el modelo 3D usando tu ratón o pantalla táctil.' 
            : '💡 Disfruta de la vista guiada en video.';
    }

    if (type === 'interactivo') {
        if (videoContainer) videoContainer.style.display = 'none';
        if (videoPlayer) {
            videoPlayer.pause();
            videoPlayer.removeAttribute('src');
            videoPlayer.innerHTML = '';
        }
        if (iframeContainer) iframeContainer.style.display = 'block';
        if (iframePlayer) {
            iframePlayer.src = tourUrl;
        }
    } else if (type === 'video') {
        if (iframeContainer) iframeContainer.style.display = 'none';
        if (iframePlayer) iframePlayer.src = '';
        if (videoContainer) videoContainer.style.display = 'block';
        if (videoPlayer) {
            videoPlayer.innerHTML = `<source src="${videoUrl}" type="video/mp4">Tu navegador no soporta video.`;
            videoPlayer.load();
            const playPromise = videoPlayer.play();
            if (playPromise !== undefined) {
                playPromise.catch(function (e) { console.log('Autoplay prevent or error:', e); });
            }
        }
    }

    // Ocultar modal de detalle temporalmente y abrir modal de tour
    closeModal('propertyModal');
    openModal('tourModal');
}

function closeTourModal() {
    const videoPlayer = document.getElementById('tourVideoPlayer');
    if (videoPlayer) {
        videoPlayer.pause();
        videoPlayer.currentTime = 0;
    }
    const iframePlayer = document.getElementById('tourIframePlayer');
    if (iframePlayer) {
        iframePlayer.src = ''; // Detener canvas/scripts del iframe
    }

    closeModal('tourModal');
    // Volver a abrir la ficha de la propiedad si venía de ahí
    if (currentPropertyId) {
        openModal('propertyModal');
    }
}

/* =========================================================
   FILTRO DE PROPIEDADES
========================================================= */

window.currentSearchFilters = {
    type: 'todos',
    operation: 'todos',
    price: 'todos',
    location: ''
};

function searchProperties() {
    const typeEl      = document.getElementById('searchType');
    const operationEl = document.getElementById('searchOperation');
    const priceEl     = document.getElementById('searchPrice');
    const locationEl  = document.getElementById('searchLocation');

    const type      = typeEl ? typeEl.value : window.currentSearchFilters.type;
    const operation = operationEl ? operationEl.value : window.currentSearchFilters.operation;
    const price     = priceEl ? priceEl.value : window.currentSearchFilters.price;
    const location  = locationEl ? locationEl.value.toLowerCase() : window.currentSearchFilters.location;

    const cards = document.querySelectorAll('.property-card');
    let found = 0;

    cards.forEach(function (card) {
        const typeOK      = type === 'todos'      || card.dataset.type === type;
        const operationOK = operation === 'todos'  || card.dataset.operation === operation;
        const priceOK     = price === 'todos'      || card.dataset.price === price;
        const locationOK  = location === ''        || card.dataset.location.toLowerCase().includes(location);

        const visible = typeOK && operationOK && priceOK && locationOK;
        card.style.display = visible ? '' : 'none';
        if (visible) found++;
    });

    document.getElementById('propiedades').scrollIntoView({ behavior: 'smooth' });

    if (typeof resetCarouselPosition === 'function') {
        resetCarouselPosition();
    }

    if (found === 0) {
        setTimeout(function () {
            showToast('No encontramos propiedades con esos filtros.', 'info');
        }, 400);
    }
}

function filterCategory(type) {
    const searchType = document.getElementById('searchType');
    if (searchType) {
        searchType.value = type;
    } else {
        window.currentSearchFilters.type = type;
    }
    searchProperties();
}

function filterOperation(op) {
    const searchOp = document.getElementById('searchOperation');
    if (searchOp) {
        searchOp.value = op;
    } else {
        window.currentSearchFilters.operation = op;
    }

    // Update active pill styling
    const pills = document.querySelectorAll('.property-filters .pill-btn');
    pills.forEach(pill => pill.classList.remove('active'));
    
    // Find the pill that matches
    pills.forEach(pill => {
        if (op === 'todos' && pill.textContent.trim() === 'Todas') pill.classList.add('active');
        if (op === 'comprar' && pill.textContent.trim() === 'En Venta') pill.classList.add('active');
        if (op === 'rentar' && pill.textContent.trim() === 'En Renta') pill.classList.add('active');
    });

    searchProperties();
}

/* =========================================================
   MOSTRAR TODAS LAS PROPIEDADES & GESTIÓN DE INFORMACIÓN
========================================================= */

function showAllProperties() {
    document.querySelectorAll('.property-card').forEach(function (card) {
        card.style.display = '';
    });
    if (typeof resetCarouselPosition === 'function') {
        resetCarouselPosition();
    }
}

/**
 * Al hacer clic en "Ver más información":
 * Abre la ficha de la propiedad donde el usuario puede ver la foto, datos,
 * el recorrido virtual (video o 3D) y el formulario para registrarse.
 */
function handleMoreInfo(propertyId) {
    showProperty(propertyId);
}

/* =========================================================
   CARRUSEL DE PROPIEDADES (REMOVIDO PARA USAR GRID)
========================================================= */

function resetCarouselPosition() {
    // Ya no hay carrusel, pero si alguien llama a esta función que no haga nada.
}

/* =========================================================
   REGISTRO
========================================================= */

function register(event) {
    event.preventDefault();

    const form = event.target;
    const isEmbedded = form.querySelector('#regNameModal') !== null;

    const nameEl     = isEmbedded ? form.querySelector('#regNameModal')     : document.getElementById('regName');
    const lastEl     = isEmbedded ? form.querySelector('#regLastModal')     : document.getElementById('regLast');
    const emailEl    = isEmbedded ? form.querySelector('#regEmailModal')    : document.getElementById('regEmail');
    const phoneEl    = isEmbedded ? form.querySelector('#regPhoneModal')    : document.getElementById('regPhone');
    const interestEl = isEmbedded ? form.querySelector('#regInterestModal') : document.getElementById('regInterest');
    const passEl     = isEmbedded ? form.querySelector('#regPasswordModal') : document.getElementById('regPassword');

    const user = {
        name:     nameEl ? nameEl.value.trim() : '',
        last:     lastEl ? lastEl.value.trim() : '',
        email:    emailEl ? emailEl.value.trim() : '',
        phone:    phoneEl ? phoneEl.value.trim() : '',
        interest: interestEl ? interestEl.value : 'comprar',
        password: passEl ? passEl.value : ''
    };

    localStorage.setItem('br_usuario', JSON.stringify(user));
    closeModal('registerModal');
    closeModal('propertyModal');
    showToast('¡Registro exitoso! Ahora puedes consultar información completa.', 'success', 4000);

    setTimeout(function () {
        location.reload();
    }, 1200);
}

/* =========================================================
   LOGIN
========================================================= */

function login(event) {
    event.preventDefault();

    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const saved    = localStorage.getItem('br_usuario');

    if (!saved) {
        showToast('No existe una cuenta local. Primero regístrate.', 'error');
        return;
    }

    const user = JSON.parse(saved);

    if (user.email === email && user.password === password) {
        closeModal('loginModal');
        showToast('¡Bienvenido, ' + user.name + '!', 'success');
        setTimeout(function () {
            location.reload();
        }, 1500);
    } else {
        showToast('Correo o contraseña incorrectos.', 'error');
    }
}

/* =========================================================
   INTERÉS EN PROPIEDAD
========================================================= */

function sendInterest(event) {
    event.preventDefault();
    closeModal('interestModal');
    showToast('Solicitud enviada. Un monitor podrá comunicarse contigo.', 'success');
}

/* =========================================================
   PUBLICAR PROPIEDAD
========================================================= */

function openPublishModal() {
    const logged = localStorage.getItem('br_usuario');
    if (!logged) {
        showToast('Para publicar una propiedad primero debes crear una cuenta.', 'info');
        openModal('registerModal');
        return;
    }
    openModal('publishModal');
}

function publishProperty(event) {
    event.preventDefault();
    closeModal('publishModal');
    showToast('Propiedad registrada en modo demostración.', 'success');
}

/* =========================================================
   CITAS
========================================================= */

function openAppointment() {
    closeModal('propertyModal');
    openModal('appointmentModal');
}

function scheduleAppointment(event) {
    event.preventDefault();
    closeModal('appointmentModal');
    showToast('Cita solicitada. Revisa tus medios de contacto para confirmar.', 'success');
}

/* =========================================================
   CHAT
========================================================= */

function toggleChat() {
    const chatBox = document.getElementById('chatBox');
    chatBox.classList.toggle('active');

    // Scroll al último mensaje al abrir
    if (chatBox.classList.contains('active')) {
        const messages = document.getElementById('chatMessages');
        messages.scrollTop = messages.scrollHeight;
    }
}

function sendMessage() {
    const input    = document.getElementById('chatInput');
    const text     = input.value.trim();
    if (text === '') return;

    const messages = document.getElementById('chatMessages');

    // Sanitizar texto del usuario antes de insertar
    const userDiv = document.createElement('div');
    userDiv.className = 'message user';
    userDiv.textContent = text;
    messages.appendChild(userDiv);

    input.value = '';
    messages.scrollTop = messages.scrollHeight;

    setTimeout(function () {
        const botDiv = document.createElement('div');
        botDiv.className = 'message';
        botDiv.textContent = 'Gracias por comunicarte con Bienes Raíces. Un monitor podrá ayudarte con tu consulta.';
        messages.appendChild(botDiv);
        messages.scrollTop = messages.scrollHeight;
    }, 700);
}

/* =========================================================
   ASESOR
========================================================= */

function requestAdvisor(event) {
    event.preventDefault();
    showToast('Solicitud de asesoría enviada. Nos pondremos en contacto contigo.', 'success');
    event.target.reset();
}

/* =========================================================
   LEGAL — DEMO
========================================================= */

function demoLegal(event, title) {
    event.preventDefault();
    showToast(title + ' — sección informativa. Consulta los requisitos antes de formalizar.', 'info', 4500);
}

/* =========================================================
   USUARIO ACTIVO — al cargar página
========================================================= */

window.addEventListener('DOMContentLoaded', function () {
    const stored = localStorage.getItem('br_usuario');

    const navAuth     = document.getElementById('navAuthButtons');
    const navUserArea = document.getElementById('navUserArea');

    if (stored) {
        try {
            const data = JSON.parse(stored);

            // Ocultar botones de login/registro
            if (navAuth) navAuth.style.display = 'none';

            // Mostrar saludo + botón de cerrar sesión
            if (navUserArea && data.name) {
                navUserArea.style.display = 'flex';
                navUserArea.innerHTML = `
                    <span style="font-size:12px;letter-spacing:1px;opacity:.85;">
                        Hola, <strong>${data.name.split(' ')[0]}</strong>
                    </span>
                    <button class="btn-outline" id="logoutBtn">Cerrar sesión</button>
                `;
                document.getElementById('logoutBtn').addEventListener('click', function () {
                    localStorage.removeItem('br_usuario');
                    location.reload();
                });
            }

        } catch (e) {
            // Dato corrupto — limpiar y mostrar estado normal
            localStorage.removeItem('br_usuario');
            if (navAuth) navAuth.style.display = 'flex';
            if (navUserArea) navUserArea.style.display = 'none';
        }

    } else {
        // Sin sesión: mostrar botones de auth normalmente
        if (navAuth) navAuth.style.display = 'flex';
        if (navUserArea) navUserArea.style.display = 'none';
    }
});

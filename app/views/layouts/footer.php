</main>

<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="footer-grid">

        <div>
            <div class="logo footer-title" style="margin-bottom:14px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 38 38" fill="none" aria-hidden="true">
                    <rect width="38" height="38" rx="9" fill="#c9a86a"/>
                    <rect x="11" y="20" width="16" height="11" rx="1.5" fill="white"/>
                    <path d="M7 21 L19 10 L31 21" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <rect x="16" y="24" width="6" height="7" rx="1" fill="#c9a86a"/>
                </svg>
                <span class="logo-name" style="color:#172033;">INFONATEC</span>
            </div>
            <p>Plataforma digital para encontrar, publicar y gestionar propiedades de manera sencilla y transparente.</p>
        </div>

        <div>
            <h4 class="footer-title">EXPLORAR</h4>
            <p><a href="<?= BASE_URL ?>explorar">Explorar catálogo</a></p>
            <p><a href="<?= BASE_URL ?>vender">Vender propiedad</a></p>
            <p><a href="<?= BASE_URL ?>#asesor">Asesoría</a></p>
        </div>

        <div>
            <h4 class="footer-title">AYUDA</h4>
            <p><a href="<?= BASE_URL ?>#legal">Preguntas frecuentes</a></p>
            <p><a href="<?= BASE_URL ?>#asesor">Contacto directo</a></p>
            <p><button type="button" onclick="toggleChat()" style="background:none;border:none;padding:0;color:#657083;font:inherit;font-size:13px;line-height:2;cursor:pointer;text-align:left;">Soporte en chat</button></p>
        </div>

        <div>
            <h4 class="footer-title">LEGAL</h4>
            <p><a href="<?= BASE_URL ?>#legal">Términos y condiciones</a></p>
            <p><a href="<?= BASE_URL ?>#legal">Política de privacidad</a></p>
            <p><a href="<?= BASE_URL ?>#legal">Aviso legal</a></p>
        </div>

    </div>

    <div class="copyright">
        © <?= date('Y') ?> INFONATEC INMOBILIARIA — Plataforma inmobiliaria. Todos los derechos reservados.
    </div>

</footer>

<!-- =========================================================
     BOTÓN CHAT ASISTENTE
========================================================= -->

<button
    type="button"
    class="chat-button"
    onclick="toggleChat()"
    aria-label="Abrir chat de asistencia"
    title="Abrir chat"
>
    💬
</button>

<div class="chat-box" id="chatBox" role="complementary" aria-label="Chat de asistencia">

    <div class="chat-header">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <strong>Asistente INFONATEC</strong>
            <span class="ai-status-badge">✨ IA Activa</span>
        </div>
        <small style="color:#cbd5e1;">Asesoría inmobiliaria inteligente</small>
    </div>

    <div class="chat-messages" id="chatMessages" aria-live="polite">
        <div class="message">¡Hola! 👋 Soy tu asesor inteligente de INFONATEC. ¿En qué puedo ayudarte hoy?</div>
        <div class="message">Pregúntame sobre casas, departamentos, terrenos, créditos o recorridos 360°.</div>
    </div>

    <div class="chat-chips-container">
        <button type="button" class="chat-chip" onclick="sendQuickQuestion('¿Qué casas tienen en Monterrey?')">🏡 Casas Mty</button>
        <button type="button" class="chat-chip" onclick="sendQuickQuestion('¿Tienen recorridos virtuales 3D?')">🕶️ Tour 3D</button>
        <button type="button" class="chat-chip" onclick="sendQuickQuestion('¿Qué terrenos tienen en Mérida?')">🌴 Mérida</button>
        <button type="button" class="chat-chip" onclick="sendQuickQuestion('¿Qué opciones de crédito aceptan?')">💳 Créditos</button>
        <button type="button" class="chat-chip" onclick="sendQuickQuestion('¿Cómo puedo vender mi propiedad?')">📝 Vender</button>
    </div>

    <div class="chat-input">
        <input
            type="text"
            id="chatInput"
            placeholder="Escribe un mensaje..."
            aria-label="Escribe un mensaje al asistente"
            onkeypress="if(event.key==='Enter') sendMessage()"
        >
        <button type="button" onclick="sendMessage()" aria-label="Enviar mensaje">➤</button>
    </div>

</div>

<!-- =========================================================
     TOAST NOTIFICATIONS
========================================================= -->

<div id="toast-container" aria-live="polite" aria-atomic="false"></div>

<!-- =========================================================
     MODAL LOGIN
========================================================= -->

<div class="modal" id="loginModal" role="dialog" aria-modal="true" aria-labelledby="loginTitle">

    <div class="modal-content">

        <button type="button" class="modal-close" onclick="closeModal('loginModal')" aria-label="Cerrar modal de inicio de sesión">×</button>

        <h2 id="loginTitle">Bienvenido.</h2>
        <p class="modal-subtitle">Ingresa a tu cuenta para sincronizar favoritos y dar seguimiento a tus citas.</p>

        <form onsubmit="login(event)">

            <div class="form-group">
                <label for="loginEmail">Correo electrónico</label>
                <input type="email" id="loginEmail" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="loginPassword">Contraseña</label>
                <input type="password" id="loginPassword" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-dark" style="width:100%;">
                INICIAR SESIÓN
            </button>

        </form>

        <p style="margin-top:20px;text-align:center;color:#555;font-size:13px;">
            ¿No tienes cuenta?
            <button
                type="button"
                onclick="closeModal('loginModal'); openModal('registerModal')"
                style="border:none;background:none;text-decoration:underline;cursor:pointer;font-weight:600;"
            >
                Regístrate
            </button>
        </p>

    </div>

</div>

<!-- =========================================================
     MODAL REGISTRO
========================================================= -->

<div class="modal" id="registerModal" role="dialog" aria-modal="true" aria-labelledby="registerTitle">

    <div class="modal-content">

        <button type="button" class="modal-close" onclick="closeModal('registerModal')" aria-label="Cerrar modal de registro">×</button>

        <h2 id="registerTitle">Crear cuenta.</h2>
        <p class="modal-subtitle">Crea tu cuenta gratuita para guardar favoritos en la nube y gestionar tus propiedades.</p>

        <form onsubmit="register(event)">

            <div class="form-grid">

                <div class="form-group">
                    <label for="regName">Nombre</label>
                    <input type="text" id="regName" required autocomplete="given-name">
                </div>

                <div class="form-group">
                    <label for="regLast">Apellidos</label>
                    <input type="text" id="regLast" required autocomplete="family-name">
                </div>

                <div class="form-group">
                    <label for="regEmail">Correo</label>
                    <input type="email" id="regEmail" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="regPhone">Teléfono</label>
                    <input type="tel" id="regPhone" required autocomplete="tel">
                </div>

                <div class="form-group full">
                    <label for="regLocation">Ubicación (Ciudad / Estado)</label>
                    <input type="text" id="regLocation" required placeholder="Detectando ubicación…" autocomplete="address-level2">
                </div>

                <div class="form-group full">
                    <label for="regPassword">Contraseña (mínimo 6 caracteres)</label>
                    <input type="password" id="regPassword" minlength="6" required autocomplete="new-password">
                </div>

                <div class="form-group full">
                    <label>Aceptación y Privacidad</label>
                    <label style="display:flex;align-items:flex-start;gap:10px;text-transform:none;letter-spacing:0;font-size:12px;color:#555;">
                        <input type="checkbox" required style="width:auto;margin-top:2px;">
                        Acepto los términos y condiciones. Consiento que INFONATEC Inmobiliaria utilice mis datos esenciales (correo, teléfono y ubicación) para contactarme y personalizar las recomendaciones de propiedades.
                    </label>
                </div>

            </div>

            <button type="submit" class="btn-dark">CREAR MI CUENTA</button>

        </form>

    </div>

</div>

<!-- =========================================================
     MODAL DE INTERÉS / CONTACTO
========================================================= -->

<div class="modal" id="interestModal" role="dialog" aria-modal="true" aria-labelledby="interestTitle">

    <div class="modal-content">

        <button type="button" class="modal-close" onclick="closeModal('interestModal')" aria-label="Cerrar modal de contacto">×</button>

        <h2 id="interestTitle">Me interesa esta propiedad.</h2>
        <p class="modal-subtitle">Solo solicitamos tus datos para contactarte y enviarte la ficha técnica o cotización.</p>

        <form onsubmit="submitInterest(event)">
            <input type="hidden" id="interestPropertyId" value="">

            <div class="form-grid">

                <div class="form-group">
                    <label for="interestName">Nombre completo</label>
                    <input type="text" id="interestName" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="interestPhone">Teléfono</label>
                    <input type="tel" id="interestPhone" required autocomplete="tel">
                </div>

                <div class="form-group full">
                    <label for="interestEmail">Correo electrónico</label>
                    <input type="email" id="interestEmail" required autocomplete="email">
                </div>

                <div class="form-group full">
                    <label for="interestMsg">Mensaje</label>
                    <textarea id="interestMsg" placeholder="Tengo interés en conocer más detalles de esta propiedad..."></textarea>
                </div>

            </div>

            <button type="submit" class="btn-dark">ENVIAR SOLICITUD</button>

        </form>

    </div>

</div>

<!-- =========================================================
     MODAL CITA
========================================================= -->

<div class="modal" id="appointmentModal" role="dialog" aria-modal="true" aria-labelledby="appointmentTitle">

    <div class="modal-content">

        <button type="button" class="modal-close" onclick="closeModal('appointmentModal')" aria-label="Cerrar modal de cita">×</button>

        <h2 id="appointmentTitle">Agenda una cita.</h2>
        <p class="modal-subtitle">Selecciona cuándo deseas conocer la propiedad. Solo pedimos tus datos para confirmar la visita.</p>

        <form onsubmit="scheduleAppointment(event)">
            <input type="hidden" id="apptPropertyId" value="">

            <div class="form-grid">

                <div class="form-group">
                    <label for="apptName">Nombre completo</label>
                    <input type="text" id="apptName" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="apptEmail">Correo electrónico</label>
                    <input type="email" id="apptEmail" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="apptDate">Fecha</label>
                    <input type="date" id="apptDate" required>
                </div>

                <div class="form-group">
                    <label for="apptTime">Hora</label>
                    <input type="time" id="apptTime" required>
                </div>

                <div class="form-group">
                    <label for="apptPhone">Teléfono</label>
                    <input type="tel" id="apptPhone" required autocomplete="tel">
                </div>

                <div class="form-group">
                    <label for="apptContact">Medio de contacto</label>
                    <select id="apptContact">
                        <option value="WHATSAPP">WhatsApp</option>
                        <option value="LLAMADA">Llamada</option>
                        <option value="CORREO">Correo</option>
                        <option value="SISTEMA">Mensaje dentro del sistema</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="apptNotes">Comentarios adicionales (opcional)</label>
                    <input type="text" id="apptNotes" placeholder="Ej. Me gustaría revisar opciones de crédito o escrituración">
                </div>

            </div>

            <button class="btn-dark" type="submit">CONFIRMAR CITA</button>

        </form>

    </div>

</div>

<!-- =========================================================
     MODAL DETALLE PROPIEDAD (FICHA COMPLETA + GALERÍA REAL)
========================================================= -->

<div class="modal" id="propertyModal" role="dialog" aria-modal="true" aria-labelledby="detailTitle">

    <div class="modal-content">

        <button type="button" class="modal-close" onclick="closeModal('propertyModal')" aria-label="Cerrar detalle de propiedad">×</button>

        <!-- Galería interactiva con navegación Anterior / Siguiente, miniaturas y teclado -->
        <div class="property-gallery" id="propertyGallery" role="region" aria-label="Galería de fotografías del inmueble">
            <div class="gallery-stage" id="galleryStage">
                <div class="gallery-top-controls">
                    <span class="gallery-counter" id="galleryCounter" aria-live="polite">1 / 1</span>
                </div>
                <img
                    id="detailImage"
                    class="detail-image"
                    src=""
                    alt=""
                    onerror="this.onerror=null;this.src=(window.APP_BASE_URL || '/') + 'img/property-fallback.svg';"
                >
                <button type="button" class="gallery-nav-btn gallery-prev-btn" id="galleryPrevBtn" onclick="prevGallerySlide()" aria-label="Fotografía anterior">‹</button>
                <button type="button" class="gallery-nav-btn gallery-next-btn" id="galleryNextBtn" onclick="nextGallerySlide()" aria-label="Fotografía siguiente">›</button>
            </div>
            <div class="gallery-caption-bar">
                <span id="galleryCaption"></span>
                <small style="color:#64748b;">Usa ← → en tu teclado o las miniaturas</small>
            </div>
            <div class="gallery-thumbs" id="galleryThumbnails" role="list" aria-label="Miniaturas de la propiedad"></div>
        </div>

        <!-- Selector de Media -->
        <div id="mediaGallerySection" class="media-gallery-actions">
            <button type="button" id="btnMediaFotos" class="btn-media-option" onclick="openMediaTour('fotos')">
                📷 Fotos (<span id="btnMediaFotosCount">1</span>)
            </button>
            <button type="button" id="btnMediaVideo" class="btn-media-option" onclick="openMediaTour('video')">
                🎥 Video recorrido
            </button>
            <button type="button" id="btnMediaInteractivo" class="btn-media-option btn-media-option-tour" onclick="openMediaTour('interactivo')">
                🕹️ Recorrido interactivo
            </button>
            <button type="button" id="btnMediaStatic360" class="btn-media-option" onclick="openMediaTour('static360')" hidden>
                🖼️ Galería estática 360°
            </button>
        </div>

        <div class="section-label">Ficha completa de propiedad</div>

        <h2 id="detailTitle">Propiedad</h2>

        <p id="detailLocation" class="modal-subtitle property-location"></p>

        <p id="detailPrice" class="property-price"></p>

        <p id="detailDescription" class="detail-description"></p>

        <div class="detail-grid">

            <div class="detail-box">
                <h3>Información básica</h3>
                <ul class="detail-list" id="basicDetails"></ul>
            </div>

            <div class="detail-box">
                <h3>Operación</h3>
                <ul class="detail-list" id="operationDetails"></ul>
            </div>

        </div>

        <!-- Información legal, opciones de pago y contacto abiertos sin registro previo -->
        <div id="fullInformation" style="margin-top:24px;">

            <div class="detail-box">
                <h3>Información legal y documentación</h3>
                <ul class="detail-list" id="legalDetails"></ul>
            </div>

            <div class="detail-box" style="margin-top:20px;">
                <h3>Opciones de pago y financiamiento</h3>
                <div class="payment-grid" id="paymentOptions"></div>
            </div>

            <div class="detail-actions-bar">
                <button type="button" class="btn-dark" onclick="openAppointment()">AGENDAR CITA</button>
                <button type="button" class="btn-gold" onclick="openInterestModal(currentPropertyId)">ME INTERESA</button>
                <button type="button" class="detail-fav-btn" id="detailFavoriteBtn" aria-pressed="false" onclick="toggleFavorite(currentPropertyId, event)">
                    ♡ Guardar en favoritos
                </button>
            </div>

        </div>

    </div>

</div>

<!-- =========================================================
     MODAL RECORRIDO VIRTUAL (AVISO DE DATOS + GALERÍA ESTÁTICA + 360°)
========================================================= -->

<div class="modal" id="tourModal" role="dialog" aria-modal="true" aria-labelledby="tourModalTitle">

    <div class="modal-content" style="max-width:920px;padding:25px;">

        <button type="button" class="modal-close" onclick="closeTourModal()" aria-label="Cerrar recorrido virtual">×</button>

        <div class="section-label">Experiencia inmersiva</div>
        <h2 id="tourModalTitle">Recorrido Virtual</h2>
        <p id="tourModalSubtitle" class="modal-subtitle" style="margin-bottom:18px;">
            Explora las áreas y distribución de esta propiedad.
        </p>

        <!-- Aviso previo de consumo de datos antes de cargar escenas 360° -->
        <div id="tourDataGate" class="tour-data-gate" style="display:none;">
            <span class="tour-data-badge" id="tourGateDataBadge">Consumo estimado: ~4.6 MB</span>
            <h3>Aviso previo de consumo de datos</h3>
            <p>
                El recorrido interactivo 360° descarga panoramas en alta resolución. Puedes iniciarlo ahora o elegir la galería estática accesible de bajo consumo.
            </p>
            <div id="tourGateSlowWarning" class="tour-slow-warning" style="display:none;">
                ⚠️ Detectamos una conexión lenta, modo ahorro de datos o estado sin conexión. Te recomendamos abrir primero la galería estática accesible.
            </div>
            <div>
                <label class="tour-gate-remember">
                    <input type="checkbox" id="tourGateRemember">
                    Recordar mi preferencia durante esta sesión
                </label>
            </div>
            <div class="tour-gate-actions">
                <button type="button" class="btn-dark" onclick="confirmLoadInteractiveTour()">Cargar Tour 360° interactivo</button>
                <button type="button" class="btn-gold" onclick="openStaticTourGallery()">Ver galería estática accesible</button>
            </div>
        </div>

        <!-- Galería estática accesible alternativa al 360° -->
        <div id="tourStaticGallery" class="tour-static-gallery" style="display:none;">
            <div class="static-gallery-stage">
                <img
                    id="staticTourImage"
                    src=""
                    alt=""
                    onerror="this.onerror=null;this.src=(window.APP_BASE_URL || '/') + 'img/property-fallback.svg';"
                >
                <button type="button" class="gallery-nav-btn gallery-prev-btn" onclick="prevStaticTourSlide()" aria-label="Escena estática anterior">‹</button>
                <button type="button" class="gallery-nav-btn gallery-next-btn" onclick="nextStaticTourSlide()" aria-label="Escena estática siguiente">›</button>
            </div>
            <div class="static-gallery-info">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h3 id="staticTourTitle">Vista del espacio</h3>
                    <span class="gallery-counter" id="staticTourCounter">1 / 1</span>
                </div>
                <p id="staticTourDesc"></p>
            </div>
            <div class="gallery-thumbs" id="staticTourThumbs" role="list" aria-label="Escenas de la galería estática"></div>
            <div class="static-gallery-controls">
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn-outline" onclick="prevStaticTourSlide()">← Anterior</button>
                    <button type="button" class="btn-outline" onclick="nextStaticTourSlide()">Siguiente →</button>
                </div>
                <button type="button" class="btn-gold" onclick="switchStaticToInteractiveTour()">Cambiar a Tour 360° interactivo</button>
            </div>
        </div>

        <!-- Contenedor de Video -->
        <div id="tourVideoContainer" style="display:none;position:relative;width:100%;border-radius:12px;overflow:hidden;background:#000;box-shadow:0 10px 30px rgba(0,0,0,0.3);">
            <video
                id="tourVideoPlayer"
                controls
                playsinline
                preload="metadata"
                style="width:100%;display:block;max-height:65vh;object-fit:contain;background:#000;"
            >
                Tu navegador no soporta la reproducción de video HTML5.
            </video>
        </div>

        <!-- Contenedor de Recorrido 3D (iframe) -->
        <div id="tourIframeContainer" style="display:none;position:relative;width:100%;height:65vh;border-radius:12px;overflow:hidden;background:#000;box-shadow:0 10px 30px rgba(0,0,0,0.3);">
            <iframe
                id="tourIframePlayer"
                src=""
                title="Recorrido virtual 360° interactivo"
                style="width:100%;height:100%;border:none;display:block;"
                allow="fullscreen; accelerometer; gyroscope"
                loading="lazy"
            ></iframe>
        </div>

        <div class="tour-footer-bar">
            <span id="tourHelpText" style="font-size:12px;color:#475569;">
                💡 Puedes alternar entre el visor 360° y la galería estática de bajo consumo en cualquier momento.
            </span>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn-catalog-back" id="btnSwitchToStaticInModal" onclick="openStaticTourGallery()" style="display:none;">
                    🖼️ Galería estática accesible
                </button>
                <button type="button" class="btn-dark" onclick="closeTourModal()">Cerrar Recorrido</button>
            </div>
        </div>

    </div>

</div>

<!-- Catálogo único: las fichas, filtros, favoritos y chat usan estos mismos datos -->
<script>
    window.INFONATEC_PROPERTIES = <?= json_encode(
        $properties ?? [],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
</script>

<!-- JavaScript Principal -->
<script src="<?= BASE_URL ?>js/app.js?v=6.3"></script>

</body>
</html>

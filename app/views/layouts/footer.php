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
            <p>Preguntas frecuentes</p>
            <p>Contacto</p>
            <p>Soporte técnico</p>
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
    class="chat-button"
    onclick="toggleChat()"
    aria-label="Abrir chat de asistencia"
    title="Abrir chat"
>
    💬
</button>

<div class="chat-box" id="chatBox" role="complementary" aria-label="Chat de asistencia">

    <div class="chat-header">
        <strong>Asistente INFONATEC</strong>
        <br>
        <small>Responde tus dudas</small>
    </div>

    <div class="chat-messages" id="chatMessages" aria-live="polite">
        <div class="message">Hola 👋 ¿En qué podemos ayudarte?</div>
        <div class="message">Puedes preguntar por propiedades, citas, créditos o requisitos.</div>
    </div>

    <div class="chat-input">
        <input
            type="text"
            id="chatInput"
            placeholder="Escribe un mensaje..."
            aria-label="Escribe un mensaje al asistente"
            onkeypress="if(event.key==='Enter') sendMessage()"
        >
        <button onclick="sendMessage()" aria-label="Enviar mensaje">➤</button>
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

        <button class="modal-close" onclick="closeModal('loginModal')" aria-label="Cerrar modal de inicio de sesión">×</button>

        <h2 id="loginTitle">Bienvenido.</h2>
        <p class="modal-subtitle">Ingresa para consultar información completa.</p>

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

        <p style="margin-top:20px;text-align:center;color:#777;font-size:13px;">
            ¿No tienes cuenta?
            <button
                onclick="closeModal('loginModal'); openModal('registerModal')"
                style="border:none;background:none;text-decoration:underline;cursor:pointer;"
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

        <button class="modal-close" onclick="closeModal('registerModal')" aria-label="Cerrar modal de registro">×</button>

        <h2 id="registerTitle">Crear cuenta.</h2>
        <p class="modal-subtitle">Regístrate para conocer información completa y contactar al propietario.</p>

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

                <div class="form-group">
                    <label for="regPassword">Contraseña</label>
                    <input type="password" id="regPassword" required autocomplete="new-password">
                </div>

                <div class="form-group full">
                    <label>Aceptación y Privacidad</label>
                    <label style="display:flex;align-items:flex-start;gap:10px;text-transform:none;letter-spacing:0;font-size:12px;color:#555;">
                        <input type="checkbox" required style="width:auto;margin-top:2px;">
                        Acepto los términos y condiciones. Consiento que INFONATEC Inmobiliaria utilice mis datos esenciales (correo, teléfono y ubicación) para contactarme y personalizar las recomendaciones de propiedades según mi interés.
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

        <button class="modal-close" onclick="closeModal('interestModal')" aria-label="Cerrar modal">×</button>

        <h2 id="interestTitle">Me interesa esta propiedad.</h2>
        <p class="modal-subtitle">Envía tu solicitud y un asesor se pondrá en contacto contigo.</p>

        <form onsubmit="submitInterest(event)">

            <div class="form-grid">

                <div class="form-group">
                    <label for="interestName">Nombre</label>
                    <input type="text" id="interestName" required>
                </div>

                <div class="form-group">
                    <label for="interestPhone">Teléfono</label>
                    <input type="tel" id="interestPhone" required>
                </div>

                <div class="form-group full">
                    <label for="interestEmail">Correo electrónico</label>
                    <input type="email" id="interestEmail" required>
                </div>

                <div class="form-group full">
                    <label for="interestMsg">Mensaje</label>
                    <textarea id="interestMsg" placeholder="Tengo interés en esta propiedad..."></textarea>
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

        <button class="modal-close" onclick="closeModal('appointmentModal')" aria-label="Cerrar modal de cita">×</button>

        <h2 id="appointmentTitle">Agenda una cita.</h2>
        <p class="modal-subtitle">Selecciona cuándo deseas conocer la propiedad.</p>

        <form onsubmit="scheduleAppointment(event)">

            <div class="form-grid">

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
                    <input type="tel" id="apptPhone" required>
                </div>

                <div class="form-group">
                    <label for="apptContact">Medio de contacto</label>
                    <select id="apptContact">
                        <option>WhatsApp</option>
                        <option>Llamada</option>
                        <option>Correo</option>
                        <option>Mensaje dentro del sistema</option>
                    </select>
                </div>

            </div>

            <button class="btn-dark" type="submit">CONFIRMAR CITA</button>

        </form>

    </div>

</div>

<!-- =========================================================
     MODAL DETALLE PROPIEDAD
========================================================= -->

<div class="modal" id="propertyModal" role="dialog" aria-modal="true" aria-labelledby="detailTitle">

    <div class="modal-content">

        <button class="modal-close" onclick="closeModal('propertyModal')" aria-label="Cerrar detalle de propiedad">×</button>

        <img id="detailImage" class="detail-image" src="" alt="">

        <!-- Selector de Media -->
        <div id="mediaGallerySection" style="margin-top: 15px; margin-bottom: 25px; display:flex; gap: 10px; justify-content: center; flex-wrap: wrap; background: #f8f9fa; padding: 12px; border-radius: var(--radius-md); border: 1px solid #e4e9f0;">
            <button id="btnMediaFotos" class="btn-media-option" onclick="openMediaTour('fotos')" style="flex:1; min-width:100px; padding:10px; border:none; background:var(--white); border-radius:var(--radius-sm); font-weight:bold; cursor:pointer; box-shadow:var(--shadow-sm); transition:0.2s;">📷 Fotos</button>
            <button id="btnMediaVideo" class="btn-media-option" onclick="openMediaTour('video')" style="flex:1; min-width:100px; padding:10px; border:none; background:var(--white); border-radius:var(--radius-sm); font-weight:bold; cursor:pointer; box-shadow:var(--shadow-sm); transition:0.2s;">🎥 Video</button>
            <button id="btnMediaInteractivo" class="btn-media-option" onclick="openMediaTour('interactivo')" style="flex:1; min-width:160px; padding:10px; border:none; background:var(--ink); color:var(--white); border-radius:var(--radius-sm); font-weight:bold; cursor:pointer; box-shadow:var(--shadow-sm); transition:0.2s;">🕹️ Recorrido Interactivo</button>
        </div>

        <div class="section-label">Información de propiedad</div>

        <h2 id="detailTitle">Propiedad</h2>

        <p id="detailLocation" class="modal-subtitle"></p>

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

        <div id="fullInformation" style="display:none;margin-top:30px;">

            <div class="detail-box">
                <h3>Información legal</h3>
                <ul class="detail-list">
                    <li>Situación legal: Sin problemas reportados</li>
                    <li>Escritura: Disponible para revisión</li>
                    <li>Propietario: Verificado</li>
                    <li>Adeudos: Sin adeudos reportados</li>
                    <li>Uso de suelo: Verificado</li>
                    <li>Colindancias: Registradas</li>
                </ul>
            </div>

            <div class="detail-box" style="margin-top:30px;">
                <h3>Opciones de pago</h3>
                <ul class="detail-list">
                    <li>Pago en efectivo</li>
                    <li>Crédito bancario</li>
                    <li>Crédito INFONAVIT</li>
                    <li>Crédito FOVISSSTE</li>
                    <li>Financiamiento según condiciones</li>
                    <li>Número de pagos sujeto al acuerdo</li>
                </ul>
            </div>

            <div style="margin-top:30px;display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn-dark" onclick="openAppointment()">AGENDAR CITA</button>
                <button class="btn-gold" onclick="closeModal('propertyModal'); openModal('interestModal')">ME INTERESA</button>
            </div>

        </div>

        <!-- Formulario de registro para visitantes -->
        <div id="loginRequired" style="display:none;margin-top:25px;padding:25px;background:#f4f2ec;border-radius:10px;text-align:center;">
            <span style="font-size:24px;display:block;margin-bottom:10px;">🔒</span>
            <h3>Regístrate para ver toda la información</h3>
            <p style="color:#666;font-size:14px;margin-bottom:20px;">
                Crea tu cuenta gratuita para desbloquear la información legal, de financiamiento y contacto.
            </p>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                <button class="btn-dark" onclick="closeModal('propertyModal'); openModal('registerModal')">REGISTRARME</button>
                <button class="btn-outline" onclick="closeModal('propertyModal'); openModal('loginModal')" style="background:transparent;color:var(--ink);border-color:var(--ink);">INICIAR SESIÓN</button>
            </div>
        </div>

    </div>

</div>

<!-- =========================================================
     MODAL RECORRIDO VIRTUAL (VIDEO / 3D)
========================================================= -->

<div class="modal" id="tourModal" role="dialog" aria-modal="true" aria-labelledby="tourModalTitle">

    <div class="modal-content" style="max-width:900px;padding:25px;">

        <button class="modal-close" onclick="closeTourModal()" aria-label="Cerrar recorrido virtual">×</button>

        <div class="section-label">Experiencia inmersiva</div>
        <h2 id="tourModalTitle">Recorrido Virtual</h2>
        <p id="tourModalSubtitle" class="modal-subtitle" style="margin-bottom:18px;">
            Explora las áreas y distribución de esta propiedad.
        </p>

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
                style="width:100%;height:100%;border:none;display:block;"
                allow="fullscreen; accelerometer; gyroscope"
                loading="lazy"
            ></iframe>
        </div>

        <div style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <span id="tourHelpText" style="font-size:12px;color:#666;">
                💡 Puedes pausar, adelantar o ver en pantalla completa el recorrido.
            </span>
            <button class="btn-dark" onclick="closeTourModal()">Cerrar Recorrido</button>
        </div>

    </div>

</div>

<!-- JavaScript Principal -->
<script src="<?= BASE_URL ?>js/app.js?v=5.0"></script>

</body>
</html>

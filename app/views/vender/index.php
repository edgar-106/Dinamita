<style>
.vender-hero {
    min-height: 85vh;
    background: linear-gradient(rgba(13, 16, 21, 0.82), rgba(13, 16, 21, 0.95)),
                url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=85') center/cover no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 140px 20px 80px;
    color: white;
}
.vender-hero-content {
    max-width: 820px;
    width: 100%;
}
.vender-hero-content h1 {
    font-family: Georgia, serif;
    font-size: clamp(40px, 6vw, 70px);
    margin-bottom: 20px;
}
.vender-hero-content p {
    font-size: 20px;
    color: #e2e8f0;
    margin-bottom: 40px;
}
.vender-benefits {
    display: flex;
    justify-content: center;
    gap: 36px;
    margin-bottom: 44px;
    flex-wrap: wrap;
}
.benefit-item {
    text-align: center;
    max-width: 210px;
}
.benefit-item strong {
    display: block;
    color: #c9a86a;
    font-size: 18px;
    margin-bottom: 8px;
}
.benefit-item span {
    font-size: 14px;
    color: #cbd5e1;
}
.btn-huge {
    background: white;
    color: #172033;
    border: none;
    padding: 18px 45px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 40px;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 2px;
    transition: all 0.3s ease;
    display: inline-block;
}
.btn-huge:hover {
    background: #c9a86a;
    color: #111827;
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(201, 168, 106, 0.4);
}
.form-panel {
    display: none;
    padding: 120px 5% 80px;
    background: #f8fafc;
    min-height: 100vh;
}
.form-container {
    max-width: 920px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.06);
    padding: 40px;
}
.progress-bar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 36px;
    position: relative;
}
.progress-bar::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    width: 100%;
    height: 3px;
    background: #e2e8f0;
    z-index: 1;
}
.progress-step {
    position: relative;
    z-index: 2;
    background: white;
    border: none;
    padding: 0 14px;
    text-align: center;
    color: #64748b;
    font-weight: bold;
    font-size: 13px;
    cursor: pointer;
}
.progress-step:disabled {
    cursor: default;
}
.progress-step.active {
    color: #9e7a37;
}
.progress-step span {
    display: block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    background: #e2e8f0;
    border-radius: 50%;
    margin: 0 auto 8px;
    color: #475569;
}
.progress-step.active span {
    background: #c9a86a;
    color: #111827;
    box-shadow: 0 0 15px rgba(201, 168, 106, 0.45);
}
.progress-step.completed span {
    background: #172033;
    color: white;
}
.form-step {
    display: none;
}
.form-step.active {
    display: block;
}
.step-title {
    font-family: Georgia, serif;
    font-size: 25px;
    color: #172033;
    margin-bottom: 22px;
}
.property-types {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}
.type-card {
    border: 2px solid #cbd5e1;
    border-radius: 15px;
    padding: 22px 15px;
    text-align: center;
    cursor: pointer;
    transition: 0.25s;
    background: #fff;
    width: 100%;
}
.type-card:hover,
.type-card.selected {
    border-color: #c9a86a;
    background: rgba(201, 168, 106, 0.08);
}
.type-card .icon {
    font-size: 34px;
    margin-bottom: 8px;
}
.type-card .name {
    font-weight: 700;
    font-size: 15px;
    color: #172033;
}
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 34px;
    padding-top: 22px;
    border-top: 1px solid #e2e8f0;
}
.btn-prev {
    background: #e2e8f0;
    color: #1e293b;
    border: none;
    padding: 12px 28px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 700;
}
.btn-next {
    background: #172033;
    color: white;
    border: none;
    padding: 12px 32px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 700;
}
.btn-publish {
    background: #c9a86a;
    color: #111827;
    border: none;
    padding: 14px 36px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 800;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.btn-publish:disabled {
    opacity: 0.65;
    cursor: wait;
}
.drop-zone {
    border: 2px dashed #94a3b8;
    border-radius: 15px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    background: #f8fafc;
    transition: border-color 0.2s, background 0.2s;
}
.drop-zone.drag-over {
    border-color: #c9a86a;
    background: #fffbeb;
}
.drop-zone .icon {
    font-size: 42px;
    margin-bottom: 10px;
}
.success-state {
    text-align: center;
    padding: 55px 20px;
}
.success-icon {
    width: 80px;
    height: 80px;
    background: #16a34a;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    margin: 0 auto 22px;
}
@media (max-width: 700px) {
    .property-types { grid-template-columns: 1fr; }
    .form-container { padding: 22px; }
    .progress-step { padding: 0 4px; font-size: 11px; }
}
</style>

<!-- HERO SECTION -->
<div class="vender-hero" id="venderHero">
    <div class="vender-hero-content">
        <h1>Vende o renta tu propiedad</h1>
        <p>Publica tu inmueble de forma rápida, guiada y segura con guardado automático, validación de fotografías y redacción asistida.</p>

        <!-- Aviso de borrador guardado automáticamente -->
        <div id="venderDraftBanner" class="vender-draft-banner" hidden role="region" aria-label="Borrador guardado">
            <div>
                <strong style="color:#c9a86a;display:block;margin-bottom:4px;">💾 Tienes un borrador guardado</strong>
                <span id="venderDraftSummary" style="font-size:14px;color:#e2e8f0;"></span>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn-gold" onclick="resumeDraft()">Continuar borrador</button>
                <button type="button" class="btn-outline" onclick="discardDraftAndStart()">Empezar desde cero</button>
            </div>
        </div>

        <div class="vender-benefits">
            <div class="benefit-item">
                <strong>Guardado automático</strong>
                <span>Conserva tu avance mientras completas los 4 pasos.</span>
            </div>
            <div class="benefit-item">
                <strong>Galería validada</strong>
                <span>Carga real de hasta 10 fotografías con vista previa.</span>
            </div>
            <div class="benefit-item">
                <strong>Asistente IA editable</strong>
                <span>Genera un borrador comercial y apruébalo antes de publicar.</span>
            </div>
        </div>

        <button type="button" class="btn-huge" onclick="startPublishing()">Publicar propiedad</button>
    </div>
</div>

<!-- FORMULARIO EN 4 PASOS -->
<div class="form-panel" id="formPanel">
    <div class="form-container">

        <!-- Barra de estado de autosave -->
        <div class="vender-autosave-bar" id="venderAutosaveBar">
            <span id="autosaveStatusText" aria-live="polite">💾 Guardado automático activo</span>
            <button type="button" class="btn-clear-draft" onclick="clearDraftWithReset()">Limpiar borrador</button>
        </div>

        <nav class="progress-bar" aria-label="Progreso de publicación en 4 pasos">
            <button type="button" class="progress-step active" id="step-nav-1" onclick="jumpToCompletedStep(1)"><span>1</span>Información</button>
            <button type="button" class="progress-step" id="step-nav-2" onclick="jumpToCompletedStep(2)" disabled><span>2</span>Características</button>
            <button type="button" class="progress-step" id="step-nav-3" onclick="jumpToCompletedStep(3)" disabled><span>3</span>Fotografías</button>
            <button type="button" class="progress-step" id="step-nav-4" onclick="jumpToCompletedStep(4)" disabled><span>4</span>Publicar</button>
        </nav>

        <form id="publishForm" novalidate onsubmit="event.preventDefault(); submitPublishForm();">

            <!-- PASO 1: INFORMACIÓN -->
            <div class="form-step active" id="step-1">
                <h2 class="step-title">Tipo de Propiedad</h2>
                <div class="property-types" role="radiogroup" aria-label="Tipo de inmueble">
                    <button type="button" class="type-card selected" role="radio" aria-checked="true" onclick="selectType('casa')" id="type-casa">
                        <div class="icon" aria-hidden="true">🏠</div>
                        <div class="name">Casa</div>
                    </button>
                    <button type="button" class="type-card" role="radio" aria-checked="false" onclick="selectType('departamento')" id="type-departamento">
                        <div class="icon" aria-hidden="true">🏢</div>
                        <div class="name">Departamento</div>
                    </button>
                    <button type="button" class="type-card" role="radio" aria-checked="false" onclick="selectType('terreno')" id="type-terreno">
                        <div class="icon" aria-hidden="true">🌳</div>
                        <div class="name">Terreno</div>
                    </button>
                </div>

                <h2 class="step-title" style="margin-top:32px;">Información Básica</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="pubOperation">Operación</label>
                        <select id="pubOperation">
                            <option value="VENTA">Venta</option>
                            <option value="RENTA">Renta (mensual)</option>
                        </select>
                        <span class="field-error" id="err-pubOperation" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="pubPrice">Precio estimado (MXN)</label>
                        <input type="number" id="pubPrice" min="1" step="any" placeholder="Ej. 2850000" required>
                        <span class="field-error" id="err-pubPrice" role="alert"></span>
                    </div>
                    <div class="form-group full">
                        <label for="pubTitle">Título de la publicación</label>
                        <input type="text" id="pubTitle" maxlength="180" placeholder="Ej. Hermosa residencia contemporánea" required>
                        <span class="field-error" id="err-pubTitle" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="pubLocation">Colonia o Zona</label>
                        <input type="text" id="pubLocation" placeholder="Ej. Montebello" required>
                        <span class="field-error" id="err-pubLocation" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="pubCity">Ciudad</label>
                        <input type="text" id="pubCity" placeholder="Ej. Mérida" required>
                        <span class="field-error" id="err-pubCity" role="alert"></span>
                    </div>
                    <div class="form-group full">
                        <label for="pubState">Estado</label>
                        <input type="text" id="pubState" placeholder="Ej. Yucatán" required>
                        <span class="field-error" id="err-pubState" role="alert"></span>
                    </div>
                    <div class="form-group full">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;flex-wrap:wrap;gap:8px;">
                            <label for="pubDesc" style="margin:0;">Descripción detallada (mínimo 20 caracteres)</label>
                            <button type="button" class="btn-ai-generate" onclick="generateAIDescription(this)" title="La IA redactará un borrador editable con tus datos">
                                ✨ Redactar con IA
                            </button>
                        </div>
                        <textarea id="pubDesc" placeholder="Describe las características, amenidades y entorno de la propiedad, o genera un borrador con IA..." required></textarea>
                        <span class="field-error" id="err-pubDesc" role="alert"></span>

                        <!-- Aprobación explícita de borrador generado con IA -->
                        <div id="aiApprovalBox" class="ai-approval-box" hidden>
                            <strong style="display:block;margin-bottom:4px;">✨ Borrador generado con Inteligencia Artificial</strong>
                            <p style="font-size:13px;color:#475569;margin-bottom:10px;">
                                Revisa y edita el texto generado arriba según las características reales de tu inmueble. Confirma tu aprobación antes de avanzar.
                            </p>
                            <label style="display:flex;align-items:flex-start;gap:10px;text-transform:none;letter-spacing:0;font-size:13px;color:#0f172a;font-weight:700;cursor:pointer;">
                                <input type="checkbox" id="aiApproveCheck" style="width:auto;margin-top:2px;">
                                He revisado y apruebo esta descripción generada con IA para su publicación.
                            </label>
                            <span class="field-error" id="err-aiApprove" role="alert"></span>
                            <div class="ai-approval-actions">
                                <button type="button" class="btn-clear-draft" onclick="regenerateAIDraft()">↻ Regenerar borrador</button>
                                <button type="button" class="btn-clear-draft" onclick="discardAIDraft()">✕ Descartar borrador IA</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions" style="justify-content:flex-end;">
                    <button type="button" class="btn-next" onclick="nextStep(2)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 2: CARACTERÍSTICAS -->
            <div class="form-step" id="step-2">
                <h2 class="step-title">Características Principales</h2>

                <div class="form-grid" id="fields-vivienda">
                    <div class="form-group">
                        <label for="pubBedrooms">Número de habitaciones</label>
                        <input type="number" id="pubBedrooms" min="0" max="50" placeholder="Ej. 3">
                        <span class="field-error" id="err-pubBedrooms" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="pubBathrooms">Número de baños</label>
                        <input type="number" id="pubBathrooms" min="0" max="50" placeholder="Ej. 2">
                        <span class="field-error" id="err-pubBathrooms" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="pubParking">Lugares de estacionamiento</label>
                        <input type="number" id="pubParking" min="0" max="50" placeholder="Ej. 2">
                        <span class="field-error" id="err-pubParking" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="pubArea">Metros cuadrados (Construcción)</label>
                        <input type="number" id="pubArea" min="1" step="any" placeholder="m²">
                        <span class="field-error" id="err-pubArea" role="alert"></span>
                    </div>
                </div>

                <div class="form-grid" id="fields-terreno" style="display:none;">
                    <div class="form-group">
                        <label for="pubLandArea">Metros cuadrados (Superficie total)</label>
                        <input type="number" id="pubLandArea" min="1" step="any" placeholder="m²">
                        <span class="field-error" id="err-pubLandArea" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="pubLandUse">Uso de suelo</label>
                        <select id="pubLandUse">
                            <option value="Residencial">Residencial</option>
                            <option value="Comercial">Comercial</option>
                            <option value="Industrial">Industrial</option>
                            <option value="Campestre / Agrícola">Campestre / Agrícola</option>
                        </select>
                        <span class="field-error" id="err-pubLandUse" role="alert"></span>
                    </div>
                    <div class="form-group full">
                        <label for="pubServices">Servicios disponibles</label>
                        <input type="text" id="pubServices" placeholder="Agua, Luz, Drenaje, Pavimento, etc.">
                        <span class="field-error" id="err-pubServices" role="alert"></span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(1)">← Atrás</button>
                    <button type="button" class="btn-next" onclick="nextStep(3)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 3: FOTOGRAFÍAS -->
            <div class="form-step" id="step-3">
                <h2 class="step-title">Galería de Imágenes</h2>
                <p id="photoDraftNotice" style="display:none;background:#fef3c7;border:1px solid #f59e0b;color:#92400e;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:14px;">
                    Tus datos de texto se restauraron del borrador. Por seguridad del navegador, selecciona nuevamente las fotografías antes de publicar.
                </p>

                <div class="drop-zone" id="photoDropZone">
                    <div class="icon" aria-hidden="true">📷</div>
                    <p style="font-weight:700;color:#172033;margin-bottom:6px;">Arrastra tus fotografías aquí o selecciónalas desde tu equipo</p>
                    <small style="color:#475569;display:block;margin-bottom:14px;">Formatos permitidos: JPG, PNG, WEBP · Máximo 5 MB por foto · Entre 1 y 10 fotografías</small>
                    <button type="button" class="btn-dark" onclick="document.getElementById('fileUpload').click()">
                        Seleccionar fotografías
                    </button>
                    <input type="file" id="fileUpload" accept="image/jpeg,image/png,image/webp" multiple style="display:none;">
                </div>

                <span class="field-error" id="err-photos" role="alert" style="margin-top:10px;"></span>
                <div id="photoPreviewGrid" class="photo-preview-grid" aria-live="polite"></div>

                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(2)">← Atrás</button>
                    <button type="button" class="btn-next" onclick="nextStep(4)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 4: CONTACTO Y PUBLICACIÓN -->
            <div class="form-step" id="step-4">
                <h2 class="step-title">Datos de Contacto y Revisión Final</h2>
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="ownerName">Nombre completo</label>
                        <input type="text" id="ownerName" placeholder="Tu nombre y apellidos" required>
                        <span class="field-error" id="err-ownerName" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="ownerPhone">Teléfono de contacto (10 dígitos)</label>
                        <input type="tel" id="ownerPhone" placeholder="Ej. 9511234567" required>
                        <span class="field-error" id="err-ownerPhone" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="ownerEmail">Correo electrónico</label>
                        <input type="email" id="ownerEmail" placeholder="correo@ejemplo.com" required>
                        <span class="field-error" id="err-ownerEmail" role="alert"></span>
                    </div>
                </div>

                <div class="vender-summary-box" id="venderSummaryBox">
                    <h3>Resumen de tu publicación</h3>
                    <div class="vender-summary-grid" id="venderSummaryGrid"></div>
                </div>

                <span class="field-error" id="publishFormError" role="alert" style="margin-top:14px;font-size:13px;"></span>

                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(3)">← Atrás</button>
                    <button type="submit" class="btn-publish" id="btnSubmitPublish">PUBLICAR PROPIEDAD</button>
                </div>
            </div>

            <!-- VISTA DE ÉXITO -->
            <div class="form-step" id="step-success">
                <div class="success-state">
                    <div class="success-icon" aria-hidden="true">✓</div>
                    <h2>¡Tu propiedad ha sido publicada con éxito!</h2>
                    <p id="publishSuccessMsg" style="color:#475569;margin:12px auto 28px;max-width:520px;">
                        Ya se encuentra disponible en el catálogo público de INFONATEC junto con sus fotografías.
                    </p>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                        <a href="<?= BASE_URL ?>explorar" class="btn-huge" style="text-decoration:none;background:#c9a86a;color:#111827;">Ver en Explorar</a>
                        <a href="<?= BASE_URL ?>" class="btn-huge" style="text-decoration:none;background:#172033;color:white;">Volver al Inicio</a>
                    </div>
                </div>
            </div>

        </form>

    </div>
</div>

<script>
const VENDER_DRAFT_KEY = 'infonatec_vender_draft_v1';
const MAX_UPLOAD_PHOTOS = 10;
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

let currentStep = 1;
let maxReachedStep = 1;
window.selectedType = 'casa';
let aiDraftGenerated = false;
let aiDraftApproved = false;
let selectedPhotos = [];
let autosaveTimer = null;

const VENDER_FIELD_IDS = [
    'pubOperation', 'pubTitle', 'pubPrice', 'pubLocation', 'pubCity', 'pubState', 'pubDesc',
    'pubBedrooms', 'pubBathrooms', 'pubParking', 'pubArea',
    'pubLandArea', 'pubLandUse', 'pubServices',
    'ownerName', 'ownerPhone', 'ownerEmail'
];

function startPublishing() {
    document.getElementById('venderHero').style.display = 'none';
    document.getElementById('formPanel').style.display = 'block';
    prefillOwnerFromSession();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prefillOwnerFromSession() {
    if (!window.INFONATEC_USER) return;
    const u = window.INFONATEC_USER;
    const nameEl = document.getElementById('ownerName');
    const phoneEl = document.getElementById('ownerPhone');
    const emailEl = document.getElementById('ownerEmail');
    if (nameEl && !nameEl.value) nameEl.value = u.name || '';
    if (phoneEl && !phoneEl.value) phoneEl.value = u.phone || '';
    if (emailEl && !emailEl.value) emailEl.value = u.email || '';
}

function selectType(type) {
    window.selectedType = type;
    document.querySelectorAll('.type-card').forEach(function (card) {
        const isSel = card.id === 'type-' + type;
        card.classList.toggle('selected', isSel);
        card.setAttribute('aria-checked', isSel ? 'true' : 'false');
    });

    if (type === 'terreno') {
        document.getElementById('fields-vivienda').style.display = 'none';
        document.getElementById('fields-terreno').style.display = 'grid';
    } else {
        document.getElementById('fields-vivienda').style.display = 'grid';
        document.getElementById('fields-terreno').style.display = 'none';
    }
    scheduleAutosave();
}

function setFieldError(inputId, errorId, message) {
    const el = document.getElementById(inputId);
    const errEl = document.getElementById(errorId);
    if (el) {
        el.classList.toggle('is-invalid', Boolean(message));
        el.setAttribute('aria-invalid', message ? 'true' : 'false');
    }
    if (errEl) {
        errEl.textContent = message || '';
    }
    return !message;
}

function validateSingleField(id) {
    const el = document.getElementById(id);
    if (!el) return true;
    const val = String(el.value || '').trim();

    switch (id) {
        case 'pubTitle':
            if (val.length < 5) return setFieldError(id, 'err-pubTitle', 'Escribe un título descriptivo de al menos 5 caracteres.');
            return setFieldError(id, 'err-pubTitle', '');
        case 'pubPrice': {
            const num = Number(val);
            if (!val || isNaN(num) || num <= 0) return setFieldError(id, 'err-pubPrice', 'Ingresa un precio numérico mayor a 0.');
            return setFieldError(id, 'err-pubPrice', '');
        }
        case 'pubLocation':
            if (val.length < 2) return setFieldError(id, 'err-pubLocation', 'Indica la colonia o zona.');
            return setFieldError(id, 'err-pubLocation', '');
        case 'pubCity':
            if (val.length < 2) return setFieldError(id, 'err-pubCity', 'Indica la ciudad.');
            return setFieldError(id, 'err-pubCity', '');
        case 'pubState':
            if (val.length < 2) return setFieldError(id, 'err-pubState', 'Indica el estado.');
            return setFieldError(id, 'err-pubState', '');
        case 'pubDesc':
            if (val.length < 20) return setFieldError(id, 'err-pubDesc', 'La descripción debe tener al menos 20 caracteres (' + val.length + '/20).');
            return setFieldError(id, 'err-pubDesc', '');
        case 'pubBedrooms':
            if (window.selectedType === 'terreno') return setFieldError(id, 'err-pubBedrooms', '');
            if (val === '' || isNaN(Number(val)) || Number(val) < 0) return setFieldError(id, 'err-pubBedrooms', 'Indica el número de habitaciones (0 o más).');
            return setFieldError(id, 'err-pubBedrooms', '');
        case 'pubBathrooms':
            if (window.selectedType === 'terreno') return setFieldError(id, 'err-pubBathrooms', '');
            if (val === '' || isNaN(Number(val)) || Number(val) < 0) return setFieldError(id, 'err-pubBathrooms', 'Indica el número de baños (0 o más).');
            return setFieldError(id, 'err-pubBathrooms', '');
        case 'pubParking':
            if (window.selectedType === 'terreno' || val === '') return setFieldError(id, 'err-pubParking', '');
            if (isNaN(Number(val)) || Number(val) < 0) return setFieldError(id, 'err-pubParking', 'Debe ser 0 o mayor.');
            return setFieldError(id, 'err-pubParking', '');
        case 'pubArea':
            if (window.selectedType === 'terreno') return setFieldError(id, 'err-pubArea', '');
            if (!val || isNaN(Number(val)) || Number(val) <= 0) return setFieldError(id, 'err-pubArea', 'Ingresa los m² de construcción (> 0).');
            return setFieldError(id, 'err-pubArea', '');
        case 'pubLandArea':
            if (window.selectedType !== 'terreno') return setFieldError(id, 'err-pubLandArea', '');
            if (!val || isNaN(Number(val)) || Number(val) <= 0) return setFieldError(id, 'err-pubLandArea', 'Ingresa la superficie total en m² (> 0).');
            return setFieldError(id, 'err-pubLandArea', '');
        case 'ownerName':
            if (val.length < 3) return setFieldError(id, 'err-ownerName', 'Ingresa tu nombre completo.');
            return setFieldError(id, 'err-ownerName', '');
        case 'ownerPhone': {
            const digits = val.replace(/\D+/g, '');
            if (digits.length < 10) return setFieldError(id, 'err-ownerPhone', 'Ingresa un teléfono válido de al menos 10 dígitos.');
            return setFieldError(id, 'err-ownerPhone', '');
        }
        case 'ownerEmail': {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(val)) return setFieldError(id, 'err-ownerEmail', 'Ingresa un correo electrónico válido.');
            return setFieldError(id, 'err-ownerEmail', '');
        }
        default:
            return true;
    }
}

function validateStep(step) {
    let firstInvalidId = null;
    let valid = true;

    const check = function (id) {
        if (!validateSingleField(id)) {
            if (!firstInvalidId) firstInvalidId = id;
            valid = false;
        }
    };

    if (step === 1) {
        ['pubTitle', 'pubPrice', 'pubLocation', 'pubCity', 'pubState', 'pubDesc'].forEach(check);
        if (aiDraftGenerated && !aiDraftApproved) {
            setFieldError('aiApproveCheck', 'err-aiApprove', 'Marca esta casilla para aprobar explícitamente el borrador generado con IA.');
            if (!firstInvalidId) firstInvalidId = 'aiApproveCheck';
            valid = false;
        } else {
            setFieldError('aiApproveCheck', 'err-aiApprove', '');
        }
    } else if (step === 2) {
        if (window.selectedType === 'terreno') {
            check('pubLandArea');
        } else {
            ['pubBedrooms', 'pubBathrooms', 'pubParking', 'pubArea'].forEach(check);
        }
    } else if (step === 3) {
        const errPhotos = document.getElementById('err-photos');
        if (selectedPhotos.length < 1) {
            if (errPhotos) errPhotos.textContent = 'Agrega al menos 1 fotografía válida (JPG, PNG o WEBP) antes de continuar.';
            valid = false;
        } else if (selectedPhotos.length > MAX_UPLOAD_PHOTOS) {
            if (errPhotos) errPhotos.textContent = 'Máximo ' + MAX_UPLOAD_PHOTOS + ' fotografías permitidas.';
            valid = false;
        } else if (errPhotos) {
            errPhotos.textContent = '';
        }
    } else if (step === 4) {
        ['ownerName', 'ownerPhone', 'ownerEmail'].forEach(check);
    }

    if (!valid && firstInvalidId) {
        const focusEl = document.getElementById(firstInvalidId);
        if (focusEl) focusEl.focus();
        if (typeof showToast === 'function') {
            showToast('Revisa los campos marcados antes de continuar.', 'error', 3000);
        }
    }

    return valid;
}

function updateProgress(step) {
    if (step > maxReachedStep) maxReachedStep = step;
    for (let i = 1; i <= 4; i++) {
        const nav = document.getElementById('step-nav-' + i);
        if (!nav) continue;
        nav.disabled = i > maxReachedStep;
        if (i < step) {
            nav.className = 'progress-step completed';
        } else if (i === step) {
            nav.className = 'progress-step active';
        } else {
            nav.className = 'progress-step';
        }
    }
}

function goToStep(step) {
    document.getElementById('step-' + currentStep).classList.remove('active');
    currentStep = step;
    document.getElementById('step-' + currentStep).classList.add('active');
    updateProgress(step);
    if (step === 4) {
        prefillOwnerFromSession();
        renderPublishSummary();
    }
    saveDraftNow();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextStep(step) {
    if (!validateStep(currentStep)) {
        return;
    }
    goToStep(step);
}

function prevStep(step) {
    goToStep(step);
}

function jumpToCompletedStep(step) {
    if (step <= maxReachedStep && step !== currentStep) {
        if (step > currentStep && !validateStep(currentStep)) {
            return;
        }
        goToStep(step);
    }
}

/* =========================================================
   APROBACIÓN EXPLÍCITA DE BORRADOR CON IA
========================================================= */

function syncAIApprovalUI() {
    const box = document.getElementById('aiApprovalBox');
    const check = document.getElementById('aiApproveCheck');
    if (!box || !check) return;

    box.hidden = !aiDraftGenerated;
    check.checked = aiDraftApproved;
    if (aiDraftApproved) {
        setFieldError('aiApproveCheck', 'err-aiApprove', '');
    }
}

window.onAIDescriptionGenerated = function () {
    aiDraftGenerated = true;
    aiDraftApproved = false;
    validateSingleField('pubDesc');
    syncAIApprovalUI();
    scheduleAutosave();
};

function regenerateAIDraft() {
    const btn = document.querySelector('.btn-ai-generate');
    if (typeof generateAIDescription === 'function') {
        generateAIDescription(btn);
    }
}

function discardAIDraft() {
    const desc = document.getElementById('pubDesc');
    if (desc) desc.value = '';
    aiDraftGenerated = false;
    aiDraftApproved = false;
    syncAIApprovalUI();
    validateSingleField('pubDesc');
    scheduleAutosave();
}

/* =========================================================
   VALIDACIÓN DETALLADA Y VISTA PREVIA DE FOTOGRAFÍAS
========================================================= */

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) {
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function handleIncomingFiles(fileList) {
    const errors = [];
    const files = Array.from(fileList || []);

    files.forEach(function (file) {
        if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
            errors.push(file.name + ': formato no permitido (solo JPG, PNG o WEBP).');
            return;
        }
        if (file.size <= 0 || file.size > MAX_UPLOAD_BYTES) {
            errors.push(file.name + ': excede el límite de 5 MB (' + formatFileSize(file.size) + ').');
            return;
        }
        if (selectedPhotos.length >= MAX_UPLOAD_PHOTOS) {
            errors.push(file.name + ': se alcanzó el máximo de ' + MAX_UPLOAD_PHOTOS + ' fotografías.');
            return;
        }
        selectedPhotos.push({
            file: file,
            previewUrl: URL.createObjectURL(file),
            caption: '',
            name: file.name,
            sizeBytes: file.size
        });
    });

    const errEl = document.getElementById('err-photos');
    if (errEl) {
        errEl.textContent = errors.join(' · ');
    }
    const notice = document.getElementById('photoDraftNotice');
    if (notice && selectedPhotos.length > 0) {
        notice.style.display = 'none';
    }

    renderPhotoPreviews();
}

function renderPhotoPreviews() {
    const grid = document.getElementById('photoPreviewGrid');
    if (!grid) return;
    grid.innerHTML = '';

    selectedPhotos.forEach(function (item, idx) {
        const card = document.createElement('div');
        card.className = 'photo-preview-item';

        const thumbWrap = document.createElement('div');
        thumbWrap.className = 'photo-preview-thumb';
        const img = document.createElement('img');
        img.src = item.previewUrl;
        img.alt = item.caption || ('Vista previa ' + (idx + 1) + ': ' + item.name);
        thumbWrap.appendChild(img);

        if (idx === 0) {
            const badge = document.createElement('span');
            badge.className = 'photo-cover-badge';
            badge.textContent = 'Portada';
            thumbWrap.appendChild(badge);
        }

        const meta = document.createElement('div');
        meta.className = 'photo-preview-meta';

        const info = document.createElement('small');
        info.style.color = '#475569';
        info.style.fontSize = '11px';
        info.textContent = item.name + ' (' + formatFileSize(item.sizeBytes) + ')';

        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.placeholder = 'Descripción (ej. Fachada principal)';
        captionInput.value = item.caption;
        captionInput.setAttribute('aria-label', 'Descripción de fotografía ' + (idx + 1));
        captionInput.addEventListener('input', function () {
            item.caption = captionInput.value;
        });

        const controls = document.createElement('div');
        controls.className = 'photo-preview-controls';

        const moveBtns = document.createElement('div');
        moveBtns.style.display = 'flex';
        moveBtns.style.gap = '4px';

        const leftBtn = document.createElement('button');
        leftBtn.type = 'button';
        leftBtn.textContent = '←';
        leftBtn.disabled = idx === 0;
        leftBtn.setAttribute('aria-label', 'Mover fotografía hacia la izquierda');
        leftBtn.onclick = function () { movePhoto(idx, -1); };

        const rightBtn = document.createElement('button');
        rightBtn.type = 'button';
        rightBtn.textContent = '→';
        rightBtn.disabled = idx === selectedPhotos.length - 1;
        rightBtn.setAttribute('aria-label', 'Mover fotografía hacia la derecha');
        rightBtn.onclick = function () { movePhoto(idx, 1); };

        moveBtns.appendChild(leftBtn);
        moveBtns.appendChild(rightBtn);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn-remove-photo';
        removeBtn.textContent = 'Quitar';
        removeBtn.setAttribute('aria-label', 'Quitar fotografía ' + item.name);
        removeBtn.onclick = function () { removePhoto(idx); };

        controls.appendChild(moveBtns);
        controls.appendChild(removeBtn);

        meta.appendChild(info);
        meta.appendChild(captionInput);
        meta.appendChild(controls);

        card.appendChild(thumbWrap);
        card.appendChild(meta);
        grid.appendChild(card);
    });
}

function movePhoto(index, delta) {
    const target = index + delta;
    if (target < 0 || target >= selectedPhotos.length) return;
    const temp = selectedPhotos[index];
    selectedPhotos[index] = selectedPhotos[target];
    selectedPhotos[target] = temp;
    renderPhotoPreviews();
}

function removePhoto(index) {
    const removed = selectedPhotos.splice(index, 1)[0];
    if (removed && removed.previewUrl) {
        URL.revokeObjectURL(removed.previewUrl);
    }
    renderPhotoPreviews();
}

/* =========================================================
   GUARDADO AUTOMÁTICO DE BORRADOR (AUTOSAVE)
========================================================= */

function collectDraftData() {
    const fields = {};
    VENDER_FIELD_IDS.forEach(function (id) {
        const el = document.getElementById(id);
        if (el) fields[id] = el.value;
    });
    return {
        step: currentStep,
        maxReachedStep: maxReachedStep,
        selectedType: window.selectedType,
        aiDraftGenerated: aiDraftGenerated,
        aiDraftApproved: aiDraftApproved,
        fields: fields,
        savedAt: new Date().toISOString()
    };
}

function saveDraftNow() {
    try {
        const payload = collectDraftData();
        localStorage.setItem(VENDER_DRAFT_KEY, JSON.stringify(payload));
        const timeStr = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const statusEl = document.getElementById('autosaveStatusText');
        if (statusEl) {
            statusEl.textContent = '💾 Borrador guardado automáticamente a las ' + timeStr;
        }
    } catch (e) {
        // Ignorar si localStorage no está disponible
    }
}

function scheduleAutosave() {
    if (autosaveTimer) clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(saveDraftNow, 350);
}

function checkExistingDraft() {
    try {
        const raw = localStorage.getItem(VENDER_DRAFT_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft || !draft.fields) return;

        const title = (draft.fields.pubTitle || '').trim() || 'Propiedad sin título';
        const hasContent = Object.values(draft.fields).some(function (v) { return String(v || '').trim() !== ''; });
        if (!hasContent) return;

        const banner = document.getElementById('venderDraftBanner');
        const summary = document.getElementById('venderDraftSummary');
        if (banner && summary) {
            summary.textContent = '"' + title + '" — Paso ' + (draft.step || 1) + ' de 4';
            banner.hidden = false;
        }
    } catch (e) {
        localStorage.removeItem(VENDER_DRAFT_KEY);
    }
}

function resumeDraft() {
    try {
        const raw = localStorage.getItem(VENDER_DRAFT_KEY);
        if (!raw) {
            startPublishing();
            return;
        }
        const draft = JSON.parse(raw);
        if (draft.selectedType) selectType(draft.selectedType);

        if (draft.fields) {
            VENDER_FIELD_IDS.forEach(function (id) {
                const el = document.getElementById(id);
                if (el && draft.fields[id] !== undefined) {
                    el.value = draft.fields[id];
                }
            });
        }

        aiDraftGenerated = Boolean(draft.aiDraftGenerated);
        aiDraftApproved = Boolean(draft.aiDraftApproved);
        syncAIApprovalUI();

        maxReachedStep = Math.min(4, Math.max(1, Number(draft.maxReachedStep || draft.step || 1)));
        const targetStep = Math.min(4, Math.max(1, Number(draft.step || 1)));

        startPublishing();
        document.getElementById('step-' + currentStep).classList.remove('active');
        currentStep = targetStep;
        document.getElementById('step-' + currentStep).classList.add('active');
        updateProgress(currentStep);

        if (currentStep >= 3 && selectedPhotos.length === 0) {
            const notice = document.getElementById('photoDraftNotice');
            if (notice) notice.style.display = 'block';
        }
        if (currentStep === 4) {
            renderPublishSummary();
        }
    } catch (e) {
        startPublishing();
    }
}

function discardDraftAndStart() {
    localStorage.removeItem(VENDER_DRAFT_KEY);
    const banner = document.getElementById('venderDraftBanner');
    if (banner) banner.hidden = true;
    startPublishing();
}

function clearDraftWithReset() {
    localStorage.removeItem(VENDER_DRAFT_KEY);
    const form = document.getElementById('publishForm');
    if (form) form.reset();
    selectedPhotos.forEach(function (item) {
        if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
    });
    selectedPhotos = [];
    renderPhotoPreviews();
    aiDraftGenerated = false;
    aiDraftApproved = false;
    syncAIApprovalUI();
    selectType('casa');
    maxReachedStep = 1;
    goToStep(1);
    document.querySelectorAll('.field-error').forEach(function (el) { el.textContent = ''; });
    document.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    const statusEl = document.getElementById('autosaveStatusText');
    if (statusEl) statusEl.textContent = '💾 Borrador limpiado. Guardado automático activo';
}

/* =========================================================
   RESUMEN Y ENVÍO REAL MULTIPART (FormData)
========================================================= */

function renderPublishSummary() {
    const grid = document.getElementById('venderSummaryGrid');
    if (!grid) return;

    const title = document.getElementById('pubTitle').value.trim();
    const op = document.getElementById('pubOperation').value;
    const price = Number(document.getElementById('pubPrice').value || 0).toLocaleString('es-MX');
    const loc = [
        document.getElementById('pubLocation').value.trim(),
        document.getElementById('pubCity').value.trim(),
        document.getElementById('pubState').value.trim()
    ].filter(Boolean).join(', ');
    const areaVal = (window.selectedType === 'terreno'
        ? document.getElementById('pubLandArea').value
        : document.getElementById('pubArea').value) || '0';
    const aiText = aiDraftGenerated
        ? (aiDraftApproved ? 'Borrador IA revisado y aprobado' : 'Pendiente de aprobación IA')
        : 'Redacción manual';

    grid.innerHTML =
        '<div><strong>Título:</strong> ' + escapeHtml(title) + '</div>' +
        '<div><strong>Tipo / Operación:</strong> ' + escapeHtml(window.selectedType.toUpperCase() + ' · ' + op) + '</div>' +
        '<div><strong>Precio:</strong> $' + escapeHtml(price) + (op === 'RENTA' ? ' / mes' : ' MXN') + '</div>' +
        '<div><strong>Ubicación:</strong> ' + escapeHtml(loc) + '</div>' +
        '<div><strong>Superficie:</strong> ' + escapeHtml(areaVal) + ' m²</div>' +
        '<div><strong>Fotografías:</strong> ' + selectedPhotos.length + ' archivo(s)</div>' +
        '<div><strong>Descripción:</strong> ' + escapeHtml(aiText) + '</div>';
}

async function submitPublishForm() {
    const formErr = document.getElementById('publishFormError');
    if (formErr) formErr.textContent = '';

    for (let step = 1; step <= 4; step++) {
        if (!validateStep(step)) {
            if (step !== currentStep) goToStep(step);
            return;
        }
    }

    const submitBtn = document.getElementById('btnSubmitPublish');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'PUBLICANDO PROPIEDAD...';
    }

    const location = [
        document.getElementById('pubLocation').value.trim(),
        document.getElementById('pubCity').value.trim(),
        document.getElementById('pubState').value.trim()
    ].filter(Boolean).join(', ');

    const fd = new FormData();
    fd.append('_csrf', getCsrfToken());
    fd.append('title', document.getElementById('pubTitle').value.trim());
    fd.append('type', window.selectedType);
    fd.append('operation', document.getElementById('pubOperation').value);
    fd.append('price', document.getElementById('pubPrice').value.trim());
    fd.append('location', location);
    fd.append('description', document.getElementById('pubDesc').value.trim());
    fd.append('bedrooms', document.getElementById('pubBedrooms').value.trim());
    fd.append('bathrooms', document.getElementById('pubBathrooms').value.trim());
    fd.append('parking', document.getElementById('pubParking').value.trim());
    fd.append('area', (window.selectedType === 'terreno'
        ? document.getElementById('pubLandArea').value
        : document.getElementById('pubArea').value).trim());
    fd.append('land_use', document.getElementById('pubLandUse').value);
    fd.append('services', document.getElementById('pubServices').value.trim());
    fd.append('owner_name', document.getElementById('ownerName').value.trim());
    fd.append('owner_phone', document.getElementById('ownerPhone').value.trim());
    fd.append('owner_email', document.getElementById('ownerEmail').value.trim());
    fd.append('ai_generated', aiDraftGenerated ? '1' : '0');
    fd.append('ai_approved', aiDraftApproved ? '1' : '0');

    selectedPhotos.forEach(function (item) {
        fd.append('photos[]', item.file);
        fd.append('photo_captions[]', item.caption || '');
    });

    try {
        const response = await fetch('<?= BASE_URL ?>vender/publicar', {
            method: 'POST',
            headers: apiHeaders(),
            body: fd
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'No fue posible publicar la propiedad.');
        }

        localStorage.removeItem(VENDER_DRAFT_KEY);
        selectedPhotos.forEach(function (item) {
            if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
        });

        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) progressBar.style.display = 'none';
        const autosaveBar = document.getElementById('venderAutosaveBar');
        if (autosaveBar) autosaveBar.style.display = 'none';

        document.getElementById('step-' + currentStep).classList.remove('active');
        const successStep = document.getElementById('step-success');
        const successMsg = document.getElementById('publishSuccessMsg');
        if (successMsg && result.message) successMsg.textContent = result.message;
        if (successStep) successStep.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        if (formErr) formErr.textContent = error.message || 'No fue posible publicar la propiedad.';
        if (typeof showToast === 'function') showToast(error.message || 'Error al publicar.', 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'PUBLICAR PROPIEDAD';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    checkExistingDraft();

    VENDER_FIELD_IDS.forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            validateSingleField(id);
            scheduleAutosave();
        });
        el.addEventListener('blur', function () {
            validateSingleField(id);
        });
    });

    const aiCheck = document.getElementById('aiApproveCheck');
    if (aiCheck) {
        aiCheck.addEventListener('change', function () {
            aiDraftApproved = aiCheck.checked;
            if (aiDraftApproved) {
                setFieldError('aiApproveCheck', 'err-aiApprove', '');
            }
            scheduleAutosave();
        });
    }

    const fileInput = document.getElementById('fileUpload');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            handleIncomingFiles(fileInput.files);
            fileInput.value = '';
        });
    }

    const dropZone = document.getElementById('photoDropZone');
    if (dropZone) {
        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        dropZone.addEventListener('dragleave', function () {
            dropZone.classList.remove('drag-over');
        });
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer && e.dataTransfer.files) {
                handleIncomingFiles(e.dataTransfer.files);
            }
        });
    }
});
</script>

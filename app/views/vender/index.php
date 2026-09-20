<style>
/* Estilos específicos de la página Vender */
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
    max-width: 800px;
}
.vender-hero-content h1 {
    font-family: Georgia, serif;
    font-size: clamp(40px, 6vw, 70px);
    margin-bottom: 20px;
}
.vender-hero-content p {
    font-size: 20px;
    color: #cbd5e1;
    margin-bottom: 45px;
}
.vender-benefits {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-bottom: 50px;
    flex-wrap: wrap;
}
.benefit-item {
    text-align: center;
    max-width: 200px;
}
.benefit-item strong {
    display: block;
    color: #c9a86a;
    font-size: 18px;
    margin-bottom: 10px;
}
.benefit-item span {
    font-size: 14px;
    color: #94a3b8;
}
.btn-huge {
    background: white;
    color: #172033;
    border: none;
    padding: 18px 45px;
    font-size: 17px;
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
    color: white;
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(201, 168, 106, 0.4);
}

/* Panel Formulario */
.form-panel {
    display: none;
    padding: 130px 5% 80px;
    background: #f8fafc;
    min-height: 100vh;
}
.form-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.05);
    padding: 45px;
}

/* Progress Bar */
.progress-bar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 45px;
    position: relative;
}
.progress-bar::before {
    content: '';
    position: absolute;
    top: 50%;
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
    padding: 0 20px;
    text-align: center;
    color: #94a3b8;
    font-weight: bold;
    font-size: 14px;
}
.progress-step.active {
    color: #c9a86a;
}
.progress-step span {
    display: block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    background: #e2e8f0;
    border-radius: 50%;
    margin: 0 auto 10px;
    color: #64748b;
}
.progress-step.active span {
    background: #c9a86a;
    color: white;
    box-shadow: 0 0 15px rgba(201,168,106,0.4);
}
.progress-step.completed span {
    background: #172033;
    color: white;
}

/* Form Steps */
.form-step {
    display: none;
}
.form-step.active {
    display: block;
}
.step-title {
    font-family: Georgia, serif;
    font-size: 26px;
    color: #172033;
    margin-bottom: 25px;
}
.property-types {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.type-card {
    border: 2px solid #e2e8f0;
    border-radius: 15px;
    padding: 25px 15px;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
}
.type-card:hover, .type-card.selected {
    border-color: #c9a86a;
    background: rgba(201,168,106,0.05);
}
.type-card .icon {
    font-size: 36px;
    margin-bottom: 10px;
}
.type-card .name {
    font-weight: bold;
    color: #172033;
}
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 25px;
    border-top: 1px solid #e2e8f0;
}
.btn-prev {
    background: #e2e8f0;
    color: #475569;
    border: none;
    padding: 12px 30px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: bold;
}
.btn-next {
    background: #172033;
    color: white;
    border: none;
    padding: 12px 35px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: bold;
}
.btn-publish {
    background: #c9a86a;
    color: white;
    border: none;
    padding: 14px 40px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
    text-transform: uppercase;
}
.drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 15px;
    padding: 50px 20px;
    text-align: center;
    cursor: pointer;
    background: #f8fafc;
}
.drop-zone .icon {
    font-size: 44px;
    margin-bottom: 12px;
}
.success-state {
    text-align: center;
    padding: 70px 20px;
}
.success-icon {
    width: 80px;
    height: 80px;
    background: #22c55e;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    margin: 0 auto 25px;
}
@media (max-width: 700px) {
    .property-types { grid-template-columns: 1fr; }
    .form-container { padding: 25px; }
}
</style>

<!-- HERO SECTION -->
<div class="vender-hero" id="venderHero">
    <div class="vender-hero-content">
        <h1>Vende tu propiedad</h1>
        <p>Publica tu inmueble de forma rápida, sencilla y segura en nuestra plataforma.</p>

        <div class="vender-benefits">
            <div class="benefit-item">
                <strong>Publicación sencilla</strong>
                <span>Completa la información de tu propiedad fácilmente.</span>
            </div>
            <div class="benefit-item">
                <strong>Mayor visibilidad</strong>
                <span>Haz que tu propiedad llegue a miles de compradores calificados.</span>
            </div>
            <div class="benefit-item">
                <strong>Proceso guiado</strong>
                <span>Te acompañamos y asesoramos en cada etapa.</span>
            </div>
        </div>

        <button class="btn-huge" onclick="startPublishing()">Publicar propiedad</button>
    </div>
</div>

<!-- FORMULARIO PASO A PASO -->
<div class="form-panel" id="formPanel">
    <div class="form-container">

        <div class="progress-bar">
            <div class="progress-step active" id="step-nav-1"><span>1</span>Información</div>
            <div class="progress-step" id="step-nav-2"><span>2</span>Características</div>
            <div class="progress-step" id="step-nav-3"><span>3</span>Fotografías</div>
            <div class="progress-step" id="step-nav-4"><span>4</span>Publicar</div>
        </div>

        <form id="publishForm" onsubmit="event.preventDefault(); publishProperty();">

            <!-- PASO 1: INFORMACIÓN -->
            <div class="form-step active" id="step-1">
                <h2 class="step-title">Tipo de Propiedad</h2>
                <div class="property-types">
                    <div class="type-card selected" onclick="selectType('casa')" id="type-casa">
                        <div class="icon">🏠</div>
                        <div class="name">Casa</div>
                    </div>
                    <div class="type-card" onclick="selectType('departamento')" id="type-departamento">
                        <div class="icon">🏢</div>
                        <div class="name">Departamento</div>
                    </div>
                    <div class="type-card" onclick="selectType('terreno')" id="type-terreno">
                        <div class="icon">🌳</div>
                        <div class="name">Terreno</div>
                    </div>
                </div>

                <h2 class="step-title" style="margin-top: 40px;">Información Básica</h2>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Título de la publicación</label>
                        <input type="text" id="pubTitle" placeholder="Ej. Hermosa residencia contemporánea" required>
                    </div>
                    <div class="form-group">
                        <label>Precio estimado (MXN)</label>
                        <input type="number" id="pubPrice" placeholder="$0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Ubicación (Colonia o Zona)</label>
                        <input type="text" id="pubLocation" placeholder="Ej. Montebello" required>
                    </div>
                    <div class="form-group">
                        <label>Ciudad</label>
                        <input type="text" id="pubCity" placeholder="Ej. Mérida" required>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <input type="text" id="pubState" placeholder="Ej. Yucatán" required>
                    </div>
                    <div class="form-group full">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
                            <label style="margin:0;">Descripción detallada</label>
                            <button type="button" class="btn-ai-generate" onclick="generateAIDescription(this)" title="La IA redactará una ficha atractiva con los datos de tu propiedad">
                                ✨ Redactar con IA
                            </button>
                        </div>
                        <textarea id="pubDesc" placeholder="Describe los detalles, amenidades y estado de la propiedad (o haz clic en 'Redactar con IA' para generarla automáticamente)..." required></textarea>
                    </div>
                </div>

                <div class="form-actions" style="justify-content: flex-end;">
                    <button type="button" class="btn-next" onclick="nextStep(2)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 2: CARACTERÍSTICAS -->
            <div class="form-step" id="step-2">
                <h2 class="step-title">Características Principales</h2>

                <div class="form-grid" id="fields-vivienda">
                    <div class="form-group">
                        <label>Número de habitaciones</label>
                        <input type="number" placeholder="Ej. 3">
                    </div>
                    <div class="form-group">
                        <label>Número de baños</label>
                        <input type="number" placeholder="Ej. 2">
                    </div>
                    <div class="form-group">
                        <label>Lugares de estacionamiento</label>
                        <input type="number" placeholder="Ej. 2">
                    </div>
                    <div class="form-group">
                        <label>Metros cuadrados (Construcción)</label>
                        <input type="number" placeholder="m²">
                    </div>
                </div>

                <div class="form-grid" id="fields-terreno" style="display: none;">
                    <div class="form-group">
                        <label>Metros cuadrados (Superficie total)</label>
                        <input type="number" placeholder="m²">
                    </div>
                    <div class="form-group">
                        <label>Uso de suelo</label>
                        <select>
                            <option>Residencial</option>
                            <option>Comercial</option>
                            <option>Industrial</option>
                            <option>Campestre / Agrícola</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Servicios disponibles</label>
                        <input type="text" placeholder="Agua, Luz, Drenaje, Pavimento, etc.">
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
                <div class="drop-zone" onclick="document.getElementById('fileUpload').click()">
                    <div class="icon">📷</div>
                    <p style="font-weight:600; color:#172033; margin-bottom:5px;">Haz clic o arrastra tus fotografías aquí</p>
                    <small style="color:#64748b;">Formatos aceptados: JPG, PNG, WEBP (Hasta 10 fotografías)</small>
                    <input type="file" id="fileUpload" accept="image/*" multiple style="display:none;">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(2)">← Atrás</button>
                    <button type="button" class="btn-next" onclick="nextStep(4)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 4: DATOS DE CONTACTO Y PUBLICAR -->
            <div class="form-step" id="step-4">
                <h2 class="step-title">Datos de Contacto del Propietario</h2>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nombre completo</label>
                        <input type="text" id="ownerName" placeholder="Tu nombre y apellidos" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono de contacto</label>
                        <input type="tel" id="ownerPhone" placeholder="10 dígitos" required>
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico</label>
                        <input type="email" id="ownerEmail" placeholder="correo@ejemplo.com" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(3)">← Atrás</button>
                    <button type="submit" class="btn-publish">PUBLICAR PROPIEDAD</button>
                </div>
            </div>

            <!-- VISTA DE ÉXITO -->
            <div class="form-step" id="step-success">
                <div class="success-state">
                    <div class="success-icon">✓</div>
                    <h2>¡Tu propiedad ha sido registrada con éxito!</h2>
                    <p>Un asesor revisará los detalles para activar la publicación en el catálogo público.</p>
                    <a href="<?= BASE_URL ?>" class="btn-huge" style="text-decoration:none; background:#172033; color:white;">Volver al Inicio</a>
                </div>
            </div>

        </form>

    </div>
</div>

<script>
let currentStep = 1;
let selectedType = 'casa';

function startPublishing() {
    document.getElementById('venderHero').style.display = 'none';
    document.getElementById('formPanel').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function selectType(type) {
    selectedType = type;
    document.querySelectorAll('.type-card').forEach(card => card.classList.remove('selected'));
    const target = document.getElementById('type-' + type);
    if (target) target.classList.add('selected');

    if (type === 'terreno') {
        document.getElementById('fields-vivienda').style.display = 'none';
        document.getElementById('fields-terreno').style.display = 'grid';
    } else {
        document.getElementById('fields-vivienda').style.display = 'grid';
        document.getElementById('fields-terreno').style.display = 'none';
    }
}

function updateProgress(step) {
    for (let i = 1; i <= 4; i++) {
        let nav = document.getElementById('step-nav-' + i);
        if (nav) {
            if (i < step) {
                nav.className = 'progress-step completed';
            } else if (i === step) {
                nav.className = 'progress-step active';
            } else {
                nav.className = 'progress-step';
            }
        }
    }
}

function nextStep(step) {
    document.getElementById('step-' + currentStep).classList.remove('active');
    currentStep = step;
    document.getElementById('step-' + currentStep).classList.add('active');
    updateProgress(step);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(step) {
    document.getElementById('step-' + currentStep).classList.remove('active');
    currentStep = step;
    document.getElementById('step-' + currentStep).classList.add('active');
    updateProgress(step);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function publishProperty() {
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) progressBar.style.display = 'none';

    document.getElementById('step-' + currentStep).classList.remove('active');
    const successStep = document.getElementById('step-success');
    if (successStep) successStep.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

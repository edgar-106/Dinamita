<?php
$html = file_get_contents('index.html');

// 1. Extract head + splash (we might remove splash later or keep it hidden)
$navStart = strpos($html, '<!-- =========================================================
     NAVBAR');
$headAndSplash = substr($html, 0, $navStart);

// Let's remove the splash screen from vender.html to make it clean?
// Actually, it's better to just extract from <!DOCTYPE to </head> and <body>, then the navbar.
// Let's just build it manually to ensure purity.

$head = substr($html, 0, strpos($html, '<!-- =========================================================
     INTRO SPLASH'));
$head .= '<body>' . "\n";

$navEnd = strpos($html, '</nav>') + 6;
$navbar = substr($html, $navStart, $navEnd - $navStart);
// Update navbar specifically for vender.html to ensure "Inicio" is present, and we highlight "Publicar"
// Wait, exploring the user's diagram, "Inicio" is needed.
// Navbar already has "Explorar", "Asesor", "Publicar", "Legal". Let's add "Inicio" at the start.
$navbar = str_replace('<ul class="nav-links" role="list">', '<ul class="nav-links" role="list">' . "\n" . '        <li><a href="index.html">Inicio</a></li>', $navbar);

$footerStart = strpos($html, '<!-- =========================================================
     FOOTER');
$footerEnd = strpos($html, '<!-- =========================================================
     MODAL LOGIN');
$footer = substr($html, $footerStart, $footerEnd - $footerStart);

$modalsStart = $footerEnd;
$modals = substr($html, $modalsStart);

$vender_content = <<<HTML
$head
<style>
/* Estilos para vender.html */
.vender-hero {
    position: relative;
    padding: 180px 5% 100px;
    background: url('https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
    color: white;
    text-align: center;
    min-height: 80vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.vender-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(13, 16, 21, 0.75);
    z-index: 1;
}
.vender-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
}
.vender-hero-content h1 {
    font-family: Georgia, serif;
    font-size: clamp(45px, 6vw, 75px);
    margin-bottom: 20px;
}
.vender-hero-content p {
    font-size: 20px;
    color: #cbd5e1;
    margin-bottom: 50px;
}
.vender-benefits {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-bottom: 60px;
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
    padding: 20px 50px;
    font-size: 18px;
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
    padding: 120px 5% 80px;
    background: #f8fafc;
    min-height: 100vh;
}
.form-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.05);
    padding: 50px;
}

/* Progress Bar */
.progress-bar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 50px;
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

/* Pasos */
.form-step {
    display: none;
    animation: fadeIn 0.4s ease;
}
.form-step.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.step-title {
    font-family: Georgia, serif;
    font-size: 32px;
    color: #172033;
    margin-bottom: 30px;
    text-align: center;
}

/* Tipo de Propiedad */
.property-types {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 40px;
}
.type-card {
    border: 2px solid #e2e8f0;
    border-radius: 15px;
    padding: 30px 40px;
    text-align: center;
    cursor: pointer;
    transition: 0.3s;
    background: white;
}
.type-card:hover {
    border-color: #c9a86a;
    transform: translateY(-5px);
}
.type-card.selected {
    border-color: #c9a86a;
    background: rgba(201,168,106,0.05);
}
.type-card .icon {
    font-size: 40px;
    margin-bottom: 15px;
}
.type-card .name {
    font-weight: bold;
    color: #172033;
}

/* Form Fields */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group.full {
    grid-column: 1 / -1;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #475569;
    font-size: 14px;
}
.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 15px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 16px;
    color: #1e293b;
    font-family: inherit;
    transition: 0.3s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: #c9a86a;
    outline: none;
    box-shadow: 0 0 0 3px rgba(201,168,106,0.1);
}
.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

/* Drag and Drop Zone */
.drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 15px;
    padding: 60px 20px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: 0.3s;
    margin-bottom: 30px;
}
.drop-zone:hover {
    border-color: #c9a86a;
    background: #fff;
}
.drop-zone .icon {
    font-size: 50px;
    color: #94a3b8;
    margin-bottom: 15px;
}
.drop-zone p {
    color: #64748b;
    font-size: 18px;
    margin-bottom: 10px;
}
.drop-zone small {
    color: #94a3b8;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #e2e8f0;
}
.btn-prev {
    background: white;
    color: #64748b;
    border: 1px solid #cbd5e1;
    padding: 15px 30px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}
.btn-prev:hover {
    background: #f1f5f9;
    color: #172033;
}
.btn-next {
    background: #172033;
    color: white;
    border: none;
    padding: 15px 40px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}
.btn-next:hover {
    background: #c9a86a;
}
.btn-publish {
    background: #c9a86a;
    color: white;
    border: none;
    padding: 15px 50px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    text-transform: uppercase;
    transition: 0.3s;
    box-shadow: 0 10px 20px rgba(201,168,106,0.3);
}
.btn-publish:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(201,168,106,0.4);
}

/* Success State */
.success-state {
    text-align: center;
    padding: 80px 20px;
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
    margin: 0 auto 30px;
    animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes scaleIn {
    0% { transform: scale(0); }
    100% { transform: scale(1); }
}
.success-state h2 {
    font-family: Georgia, serif;
    font-size: 36px;
    color: #172033;
    margin-bottom: 20px;
}
.success-state p {
    color: #64748b;
    font-size: 18px;
    margin-bottom: 40px;
}
</style>

$navbar

<!-- HERO SECTION -->
<div class="vender-hero" id="venderHero">
    <div class="vender-hero-content">
        <h1>Vende tu propiedad</h1>
        <p>Publica tu propiedad de forma rápida, sencilla y segura.</p>
        
        <div class="vender-benefits">
            <div class="benefit-item">
                <strong>Publicación sencilla</strong>
                <span>Completa la información de tu propiedad fácilmente.</span>
            </div>
            <div class="benefit-item">
                <strong>Mayor visibilidad</strong>
                <span>Haz que tu propiedad llegue a posibles compradores.</span>
            </div>
            <div class="benefit-item">
                <strong>Proceso rápido</strong>
                <span>Publica tu inmueble en pocos pasos.</span>
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
        
        <form id="publishForm" onsubmit="event.preventDefault(); submitForm();">
            <!-- PASO 1: INFORMACION -->
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
                
                <h2 class="step-title" style="margin-top: 50px;">Información Básica</h2>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Título de la publicación</label>
                        <input type="text" placeholder="Ej. Hermosa casa en venta en Cancún" required>
                    </div>
                    <div class="form-group">
                        <label>Precio</label>
                        <input type="number" placeholder="$0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Ubicación (Colonia o Calle)</label>
                        <input type="text" placeholder="Ubicación" required>
                    </div>
                    <div class="form-group">
                        <label>Ciudad</label>
                        <input type="text" placeholder="Ciudad" required>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <input type="text" placeholder="Estado" required>
                    </div>
                    <div class="form-group full">
                        <label>Descripción</label>
                        <textarea placeholder="Describe los detalles de tu propiedad..." required></textarea>
                    </div>
                </div>
                
                <div class="form-actions" style="justify-content: flex-end;">
                    <button type="button" class="btn-next" onclick="nextStep(2)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 2: CARACTERÍSTICAS -->
            <div class="form-step" id="step-2">
                <h2 class="step-title">Características</h2>
                
                <div class="form-grid" id="fields-vivienda">
                    <div class="form-group">
                        <label>Número de habitaciones</label>
                        <input type="number" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Número de baños</label>
                        <input type="number" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Estacionamientos</label>
                        <input type="number" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Metros cuadrados (Construcción)</label>
                        <input type="number" placeholder="m²">
                    </div>
                </div>
                
                <div class="form-grid" id="fields-terreno" style="display: none;">
                    <div class="form-group">
                        <label>Metros cuadrados (Superficie)</label>
                        <input type="number" placeholder="m²">
                    </div>
                    <div class="form-group">
                        <label>Tipo de terreno</label>
                        <select>
                            <option>Residencial</option>
                            <option>Comercial</option>
                            <option>Industrial</option>
                            <option>Agrícola</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Servicios disponibles</label>
                        <input type="text" placeholder="Agua, Luz, Drenaje, etc.">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(1)">← Atrás</button>
                    <button type="button" class="btn-next" onclick="nextStep(3)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 3: FOTOGRAFÍAS -->
            <div class="form-step" id="step-3">
                <h2 class="step-title">Fotografías</h2>
                <div class="drop-zone">
                    <div class="icon">📷</div>
                    <p>Arrastra tus fotografías aquí</p>
                    <small>o haz clic para seleccionar archivos (Máximo 10 fotos)</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(2)">← Atrás</button>
                    <button type="button" class="btn-next" onclick="nextStep(4)">Siguiente →</button>
                </div>
            </div>

            <!-- PASO 4: PUBLICAR -->
            <div class="form-step" id="step-4">
                <h2 class="step-title">Datos de Contacto</h2>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nombre del propietario</label>
                        <input type="text" placeholder="Tu nombre completo" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" placeholder="Tu número telefónico" required>
                    </div>
                    <div class="form-group">
                        <label>Correo electrónico</label>
                        <input type="email" placeholder="tu@correo.com" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-prev" onclick="prevStep(3)">← Atrás</button>
                    <button type="button" class="btn-publish" onclick="publishProperty()">Publicar propiedad</button>
                </div>
            </div>
            
            <!-- VISTA DE ÉXITO -->
            <div class="form-step" id="step-success">
                <div class="success-state">
                    <div class="success-icon">✓</div>
                    <h2>¡Tu propiedad ha sido publicada!</h2>
                    <p>Tu anuncio ya está visible para miles de compradores potenciales.</p>
                    <button type="button" class="btn-next" onclick="window.location.href='index.html'">Volver al Inicio</button>
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
        window.scrollTo(0, 0);
    }
    
    function selectType(type) {
        selectedType = type;
        document.querySelectorAll('.type-card').forEach(card => card.classList.remove('selected'));
        document.getElementById('type-' + type).classList.add('selected');
        
        if (type === 'terreno') {
            document.getElementById('fields-vivienda').style.display = 'none';
            document.getElementById('fields-terreno').style.display = 'grid';
        } else {
            document.getElementById('fields-vivienda').style.display = 'grid';
            document.getElementById('fields-terreno').style.display = 'none';
        }
    }
    
    function updateProgress(step) {
        for(let i=1; i<=4; i++) {
            let nav = document.getElementById('step-nav-' + i);
            if(nav) {
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
        window.scrollTo(0, 0);
    }
    
    function prevStep(step) {
        document.getElementById('step-' + currentStep).classList.remove('active');
        currentStep = step;
        document.getElementById('step-' + currentStep).classList.add('active');
        updateProgress(step);
        window.scrollTo(0, 0);
    }
    
    function publishProperty() {
        // Hide progress bar
        document.querySelector('.progress-bar').style.display = 'none';
        
        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('step-success').classList.add('active');
        window.scrollTo(0, 0);
    }
</script>

$footer
$modals
</body>
</html>
HTML;

file_put_contents('vender.html', $vender_content);
echo "vender.html created successfully.\n";
?>

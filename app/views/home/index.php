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
            <p>Selecciona el tipo de inmueble que necesitas y encuentra opciones según tu presupuesto.</p>
        </div>
        <div class="chooser-grid">
            <button class="chooser-card" onclick="selectCategory('casa')" style="background-image:url('https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Casas</strong><small>Compra o renta</small></span>
            </button>
            <button class="chooser-card" onclick="selectCategory('departamento')" style="background-image:url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Departamentos</strong><small>Espacios modernos</small></span>
            </button>
            <button class="chooser-card" onclick="selectCategory('terreno')" style="background-image:url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Terrenos</strong><small>Invierte en tu futuro</small></span>
            </button>
        </div>
    </div>

    <div class="catalog-view" id="catalogView" hidden>
        <!-- Properties Grid dinámico renderizado con PHP (MVC) -->
        <div class="properties-grid" id="mainPropGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">

            <?php foreach ($properties as $prop): ?>
            <article class="property-card js-prop-card" data-type="<?= htmlspecialchars($prop['type']) ?>" data-operation="<?= htmlspecialchars($prop['operation']) ?>" onclick="handleMoreInfo(<?= (int)$prop['id'] ?>)">
                <div class="property-image">
                    <video src="<?= BASE_URL . htmlspecialchars($prop['video']) ?>" class="property-video" muted playsinline loop onmouseover="this.play()" onmouseout="this.pause()" style="width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;"></video>
                    <span class="property-tag"><?= htmlspecialchars($prop['tag']) ?></span>
                    <button class="favorite-btn" onclick="event.stopPropagation(); this.classList.toggle('active')">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>
                </div>
                <div class="property-body" style="padding: 20px; display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <h3 class="property-title" style="margin:0; font-size:18px; color:#172033; font-weight:700;"><?= htmlspecialchars($prop['title']) ?></h3>
                    </div>
                    <div class="property-location" style="display:flex; align-items:center; gap:5px; color:#94a3b8; font-size:13px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?= htmlspecialchars($prop['location']) ?>
                    </div>
                    <?php if (!empty($prop['stats'])): ?>
                    <div class="property-stats" style="display:flex; gap:15px; margin-top:5px; flex-wrap:wrap;">
                        <span style='display:flex; align-items:center; gap:5px; font-size:12px; color:#64748b;'>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <path d="M9 3v18M15 3v18M3 9h18M3 15h18"></path>
                            </svg>
                            <?= htmlspecialchars(implode(' · ', $prop['stats'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="property-price" style="margin-top:10px; font-size:22px; font-weight:800; color:#c9a86a;">
                        <?= htmlspecialchars($prop['price']) ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>

        </div>
    </div>
</section>

<script>
let currentOp = "comprar";
let currentType = "all";
const categoryNames = { all: 'Todos', casa: 'Casas', departamento: 'Departamentos', terreno: 'Terrenos' };

function openCategoryChooser() {
    document.getElementById('categoryChooser').hidden = false;
    document.getElementById('catalogView').hidden = true;
    document.getElementById('adquirir').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function selectCategory(type) {
    currentType = type;
    const label = document.getElementById('categoryLabel');
    if (label) label.firstChild.nodeValue = categoryNames[type] + ' ';
    document.getElementById('categoryChooser').hidden = true;
    document.getElementById('catalogView').hidden = false;
    filterProps('type', type);
}

function openCatalogForOperation(operation) {
    document.getElementById('categoryChooser').hidden = true;
    document.getElementById('catalogView').hidden = false;
    currentType = 'all';
    const label = document.getElementById('categoryLabel');
    if (label) label.firstChild.nodeValue = categoryNames.all + ' ';
    filterProps('op', operation);
}

function filterProps(filterType, value) {
    if (filterType === "op") {
        currentOp = value;
        document.querySelectorAll("#op-comprar, #op-rentar").forEach(b => b.classList.remove("active"));
        const btn = document.getElementById("op-" + value);
        if (btn) btn.classList.add("active");
    } else {
        currentType = value;
    }
    document.querySelectorAll(".js-prop-card").forEach(card => {
        const matchOp = card.dataset.operation === currentOp;
        const matchType = currentType === "all" || card.dataset.type === currentType;
        card.style.display = (matchOp && matchType) ? "block" : "none";
    });
}

document.addEventListener("DOMContentLoaded", () => filterProps("op", "comprar"));
</script>

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
                <button class="btn-advisor-dark" onclick="toggleChat()" aria-label="Abrir chat en vivo">▣ Chat en Vivo</button>
                <button class="btn-advisor-green" onclick="openModal('interestModal')" aria-label="Contactar asesor">▣ Contactar asesor</button>
            </div>
        </div>
        <div class="advisor-form">
            <h3>¿Deseas atención personalizada o agendar un recorrido guiado?</h3>
            <div class="small">Déjanos tus datos y te orientaremos sobre propiedades, requisitos y citas.</div>
            <form onsubmit="requestAdvisor(event)">
                <div class="advisor-form-grid">
                    <input id="advisorName" required placeholder="Nombre completo" aria-label="Nombre completo">
                    <input id="advisorPhone" required placeholder="Teléfono / WhatsApp" aria-label="Teléfono o WhatsApp">
                    <select id="advisorTime" aria-label="Horario preferido">
                        <option>Inmediato (9:00 - 19:00)</option>
                        <option>Por la mañana</option>
                        <option>Por la tarde</option>
                    </select>
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

<style>
.legal-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: 0.3s;
}
.legal-summary {
    padding: 20px 25px;
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.legal-summary::-webkit-details-marker {
    display: none;
}
.legal-summary::after {
    content: "+";
    color: #c9a86a;
    font-size: 24px;
    font-weight: normal;
    transition: 0.3s transform;
}
.legal-item[open] .legal-summary::after {
    content: "−";
    transform: rotate(180deg);
}
.legal-item[open] {
    border-color: #c9a86a;
    box-shadow: 0 5px 15px rgba(201,168,106,0.1);
}
.legal-content {
    padding: 0 25px 25px;
    color: #475569;
    font-size: 15px;
    line-height: 1.6;
}
</style>

<section class="section legal" id="legal" style="background:#fff; padding: 80px 5%;">
    <div style="max-width:800px; margin:0 auto;">
        <div style="text-align:center; margin-bottom:50px;">
            <div style="color:#c9a86a; font-weight:bold; letter-spacing:2px; font-size:12px; text-transform:uppercase; margin-bottom:10px;">Certeza Jurídica</div>
            <h2 style="font-family:Georgia, serif; font-size:40px; color:#172033; margin:0;">Términos y Legalidad</h2>
            <p style="color:#64748b; font-size:16px; margin-top:15px;">Información clara, resumida y directa para tu tranquilidad en cada operación.</p>
        </div>
        <div class="legal-accordion" style="display:flex; flex-direction:column; gap:15px;">
            <details class="legal-item">
                <summary class="legal-summary">Promesa de Compraventa</summary>
                <div class="legal-content"><p>Contrato preparatorio que asegura condiciones, precio y plazos antes de la escrituración formal.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Arrendamiento Seguro</summary>
                <div class="legal-content"><p>Requisitos de contratación: depósito en garantía, fiador con propiedad o póliza jurídica.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Auditoría Registral (RPP)</summary>
                <div class="legal-content"><p>Revisión exhaustiva ante el Registro Público para garantizar que el inmueble está libre de gravámenes.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Protección de Datos</summary>
                <div class="legal-content"><p>Tus datos están encriptados y se utilizan exclusivamente para formalizar tu operación inmobiliaria.</p></div>
            </details>
        </div>
    </div>
</section>

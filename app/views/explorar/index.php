<style>
.explorar-header {
    position: relative;
    padding: 140px 5% 60px;
    background: #0f141d;
    text-align: center;
    color: white;
    overflow: hidden;
}
.header-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.25;
    z-index: 1;
}
.explorar-header > * {
    position: relative;
    z-index: 2;
}
.explorar-header h1 {
    font-family: Georgia, serif;
    font-size: clamp(38px, 5vw, 60px);
    margin-bottom: 15px;
}
.explorar-header p {
    color: #cbd5e1;
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto 35px;
}
.operation-tabs {
    display: inline-flex;
    background: rgba(255, 255, 255, 0.1);
    padding: 6px;
    border-radius: 40px;
    backdrop-filter: blur(10px);
    margin-bottom: 25px;
    gap: 5px;
}
.op-btn {
    background: transparent;
    border: none;
    color: #fff;
    padding: 10px 30px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: 0.3s;
    cursor: pointer;
}
.op-btn:hover, .op-btn.active {
    background: #c9a86a;
    color: #172033;
    box-shadow: 0 0 15px rgba(201,168,106,0.4);
}
.category-tabs {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.tab-btn {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 12px 30px;
    border-radius: 30px;
    font-size: 14px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: 0.3s;
    cursor: pointer;
}
.tab-btn:hover, .tab-btn.active {
    background: #c9a86a;
    border-color: #c9a86a;
    color: #172033;
}
.properties-container {
    padding: 60px 5% 100px;
    background: #f5f7fb;
    min-height: 60vh;
}
</style>

<section class="explorar-header">
    <video class="header-background" autoplay muted loop playsinline aria-hidden="true">
        <source src="<?= BASE_URL ?>casa 1.mp4" type="video/mp4">
    </video>
    <h1 id="catTitle">Casas</h1>
    <p id="catDesc">Encuentra la propiedad de tus sueños, ideal para ti y tu familia.</p>

    <div class="operation-tabs">
        <button class="op-btn active" id="op-comprar" onclick="switchOperation('comprar')">Comprar</button>
        <button class="op-btn" id="op-rentar" onclick="switchOperation('rentar')">Rentar</button>
    </div>

    <div class="category-tabs">
        <button class="tab-btn active" id="tab-casa" onclick="switchCategory('casa')">Casas</button>
        <button class="tab-btn" id="tab-departamento" onclick="switchCategory('departamento')">Departamentos</button>
        <button class="tab-btn" id="tab-terreno" onclick="switchCategory('terreno')">Terrenos</button>
    </div>
</section>

<section class="properties-container">
    <div class="property-grid" id="propertyGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">

        <?php foreach ($properties as $prop): ?>
        <article class="property-card" data-type="<?= htmlspecialchars($prop['type']) ?>" data-operation="<?= htmlspecialchars($prop['operation']) ?>" onclick="handleMoreInfo(<?= (int)$prop['id'] ?>)">
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
</section>

<script>
let currentCategory = "casa";
let currentOperation = "comprar";

const catInfo = {
    casa: {
        title: "Casas",
        desc: "Encuentra la casa de tus sueños, ideal para ti y tu familia."
    },
    departamento: {
        title: "Departamentos",
        desc: "Espacios modernos y céntricos, adaptados a tu estilo de vida."
    },
    terreno: {
        title: "Terrenos",
        desc: "Lotes residenciales y comerciales para construir tus proyectos."
    }
};

function switchCategory(cat) {
    currentCategory = cat;
    document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));
    const activeTab = document.getElementById("tab-" + cat);
    if (activeTab) activeTab.classList.add("active");

    document.getElementById("catTitle").innerText = catInfo[cat].title;
    document.getElementById("catDesc").innerText = catInfo[cat].desc;

    filterCatalog();
}

function switchOperation(op) {
    currentOperation = op;
    document.querySelectorAll(".op-btn").forEach(btn => btn.classList.remove("active"));
    const activeOp = document.getElementById("op-" + op);
    if (activeOp) activeOp.classList.add("active");

    filterCatalog();
}

function filterCatalog() {
    const cards = document.querySelectorAll("#propertyGrid .property-card");
    cards.forEach(card => {
        const type = card.getAttribute("data-type");
        const op = card.getAttribute("data-operation");
        if (type === currentCategory && op === currentOperation) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    filterCatalog();
});
</script>

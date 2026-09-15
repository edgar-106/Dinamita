<?php
$idx = file_get_contents('index.html');

// 1. Change "Compra" to "Adquirir" in splash screen
$idx = str_replace('Compra', 'Adquirir', $idx);

// 2. Replace the #categorias section with a modern property grid & filters
$startCat = strpos($idx, '<section class="section categories" id="categorias">');
if ($startCat === false) $startCat = strpos($idx, '<section class="section" id="categorias">'); // fallback
$endCat = strpos($idx, '</section>', $startCat) + 10;

// The new Adquirir Section HTML
$newAdquirir = '
<section class="section" id="adquirir" style="padding: 60px 5%; background: #f5f7fb;">
    <div class="section-header" style="text-align: center; margin-bottom: 40px;">
        <div class="section-label">Catálogo</div>
        <h2 style="font-family: Georgia, serif; font-size: clamp(32px, 5vw, 48px); margin: 0; color: #172033;">Encuentra tu espacio ideal</h2>
    </div>

    <!-- Filtros -->
    <div style="display: flex; flex-direction: column; align-items: center; gap: 20px; margin-bottom: 40px;">
        <div class="operation-tabs" style="display: flex; gap: 10px;">
            <button class="filter-btn active" id="op-comprar" onclick="filterProps(\'op\', \'comprar\')">Adquirir</button>
            <button class="filter-btn" id="op-rentar" onclick="filterProps(\'op\', \'rentar\')">Rentar</button>
        </div>
        <div class="type-tabs" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
            <button class="filter-btn active" id="type-all" onclick="filterProps(\'type\', \'all\')">Todos</button>
            <button class="filter-btn" id="type-casa" onclick="filterProps(\'type\', \'casa\')">Casas</button>
            <button class="filter-btn" id="type-departamento" onclick="filterProps(\'type\', \'departamento\')">Departamentos</button>
            <button class="filter-btn" id="type-terreno" onclick="filterProps(\'type\', \'terreno\')">Terrenos</button>
        </div>
    </div>

    <!-- Properties Grid -->
    <div class="properties-grid" id="mainPropGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">
';

$properties = [
    ['id'=>1, 'type'=>'terreno', 'op'=>'comprar', 'title'=>'Lote Montebello', 'loc'=>'Mérida, YUC', 'price'=>'$1,850,000', 'tag'=>'EXCLUSIVA', 'img'=>'casa 1.mp4', 'stats'=>'450 m²'],
    ['id'=>2, 'type'=>'casa', 'op'=>'comprar', 'title'=>'Residencia Las Cumbres', 'loc'=>'Monterrey, NL', 'price'=>'$6,800,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'4 hab · 5 baños · 350 m²'],
    ['id'=>3, 'type'=>'departamento', 'op'=>'rentar', 'title'=>'Penthouse Polanco', 'loc'=>'Polanco, CDMX', 'price'=>'$45,000', 'tag'=>'RENTA', 'img'=>'casa 1.mp4', 'stats'=>'2 hab · 2 baños · 180 m²'],
    ['id'=>4, 'type'=>'casa', 'op'=>'comprar', 'title'=>'Villa Toscana', 'loc'=>'Valle de Bravo, MEX', 'price'=>'$12,000,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'5 hab · 6 baños · 600 m²'],
    ['id'=>5, 'type'=>'departamento', 'op'=>'comprar', 'title'=>'Departamento Santa Fe', 'loc'=>'Santa Fe, CDMX', 'price'=>'$9,200,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'3 hab · 3 baños · 220 m²'],
    ['id'=>6, 'type'=>'terreno', 'op'=>'comprar', 'title'=>'Terreno Industrial', 'loc'=>'Querétaro, QRO', 'price'=>'$4,100,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'1,200 m²'],
];

foreach($properties as $p) {
    $statsHtml = "";
    $parts = explode('·', $p['stats']);
    foreach($parts as $part) {
        $part = trim($part);
        $icon = "";
        if (strpos($part, 'hab') !== false) {
            $icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>';
        } elseif (strpos($part, 'baño') !== false) {
            $icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M4 12v6a2 2 0 002 2h12a2 2 0 002-2v-6M9 4v4M15 4v4M12 2v6"></path></svg>';
        } else {
            $icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><path d="M9 3v18M15 3v18M3 9h18M3 15h18"></path></svg>';
        }
        $statsHtml .= "<span style='display:flex; align-items:center; gap:5px; font-size:12px; color:#64748b;'>$icon $part</span>";
    }

    $newAdquirir .= '
        <article class="property-card js-prop-card" data-type="'.$p['type'].'" data-operation="'.$p['op'].'" onclick="handleMoreInfo('.$p['id'].')">
            <div class="property-image">
                <video src="'.$p['img'].'" class="property-video" muted playsinline loop onmouseover="this.play()" onmouseout="this.pause()" style="width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;"></video>
                <span class="property-tag">'.$p['tag'].'</span>
                <button class="favorite-btn" onclick="event.stopPropagation(); this.classList.toggle(\'active\')">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
            </div>
            <div class="property-body" style="padding: 20px; display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <h3 class="property-title" style="margin:0; font-size:18px; color:#172033; font-weight:700;">'.$p['title'].'</h3>
                </div>
                <div class="property-location" style="display:flex; align-items:center; gap:5px; color:#94a3b8; font-size:13px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    '.$p['loc'].'
                </div>
                <div class="property-stats" style="display:flex; gap:15px; margin-top:5px; flex-wrap:wrap;">
                    '.$statsHtml.'
                </div>
                <div class="property-price" style="margin-top:10px; font-size:22px; font-weight:800; color:#c9a86a;">
                    '.$p['price'].' <span style="font-size:12px; font-weight:normal; color:#64748b;">MXN</span>
                </div>
            </div>
        </article>';
}

$newAdquirir .= '
    </div>
</section>
<script>
let currentOp = "comprar";
let currentType = "all";
function filterProps(filterType, value) {
    if (filterType === "op") {
        currentOp = value;
        document.querySelectorAll("#op-comprar, #op-rentar").forEach(b => b.classList.remove("active"));
        document.getElementById("op-" + value).classList.add("active");
    } else {
        currentType = value;
        document.querySelectorAll("[id^=type-]").forEach(b => b.classList.remove("active"));
        document.getElementById("type-" + value).classList.add("active");
    }
    document.querySelectorAll(".js-prop-card").forEach(card => {
        const matchOp = card.dataset.operation === currentOp;
        const matchType = currentType === "all" || card.dataset.type === currentType;
        if (matchOp && matchType) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}
// Init filter
document.addEventListener("DOMContentLoaded", () => filterProps("op", "comprar"));
</script>
';

if ($startCat !== false) {
    $idx = substr_replace($idx, $newAdquirir, $startCat, $endCat - $startCat);
}

// 3. New Legal Section (Accordion)
$legalHtml = '
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
';

$startL = strpos($idx, '<section class="section legal"');
if ($startL !== false) {
    $endL = strpos($idx, '</section>', $startL) + 10;
    $idx = substr_replace($idx, $legalHtml, $startL, $endL - $startL);
}

// Write index.html
file_put_contents('index.html', $idx);
echo "Updated index.html\n";

?>

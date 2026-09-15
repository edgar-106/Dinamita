<?php

// 1. UPDATE CSS for property-card
$css = file_get_contents('css/styles.css');
$cssReplace = <<<CSS
.property-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #f1f5f9;
}
.property-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}
.property-image {
    height: 240px;
    position: relative;
    overflow: hidden;
}
.property-image::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,.1), transparent 50%, rgba(0,0,0,.6));
    pointer-events: none;
}
.property-image video {
    transition: transform 0.6s ease;
}
.property-card:hover .property-image video {
    transform: scale(1.08);
}
.property-tag {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #c9a86a;
    color: white;
    padding: 6px 14px;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-weight: 800;
    border-radius: 20px;
    z-index: 2;
    box-shadow: 0 4px 10px rgba(201,168,106,0.3);
}
.favorite-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    cursor: pointer;
    z-index: 2;
    transition: 0.3s;
}
.favorite-btn:hover {
    background: #fff;
    color: #ff3b30;
    transform: scale(1.1);
}
.favorite-btn.active {
    color: #ff3b30;
}
.favorite-btn.active svg {
    fill: #ff3b30;
}
CSS;

$pattern = '/\.property-card\s*\{.*?(?=\.property-body|\.property-title|\z)/s';
$css = preg_replace($pattern, $cssReplace . "\n\n", $css, 1);
file_put_contents('css/styles.css', $css);


// 2. NEW HTML DATA FOR CARDS
$properties = [
    ['id'=>1, 'type'=>'terreno', 'op'=>'comprar', 'title'=>'Lote Montebello', 'loc'=>'Mérida, YUC', 'price'=>'$1,850,000', 'tag'=>'EXCLUSIVA', 'img'=>'casa 1.mp4', 'stats'=>'450 m²'],
    ['id'=>2, 'type'=>'casa', 'op'=>'comprar', 'title'=>'Residencia Las Cumbres', 'loc'=>'Monterrey, NL', 'price'=>'$6,800,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'4 hab · 5 baños · 350 m²'],
    ['id'=>3, 'type'=>'departamento', 'op'=>'rentar', 'title'=>'Penthouse Polanco', 'loc'=>'Polanco, CDMX', 'price'=>'$45,000', 'tag'=>'RENTA', 'img'=>'casa 1.mp4', 'stats'=>'2 hab · 2 baños · 180 m²'],
    ['id'=>4, 'type'=>'casa', 'op'=>'comprar', 'title'=>'Villa Toscana', 'loc'=>'Valle de Bravo, MEX', 'price'=>'$12,000,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'5 hab · 6 baños · 600 m²'],
    ['id'=>5, 'type'=>'departamento', 'op'=>'comprar', 'title'=>'Departamento Santa Fe', 'loc'=>'Santa Fe, CDMX', 'price'=>'$9,200,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'3 hab · 3 baños · 220 m²'],
    ['id'=>6, 'type'=>'terreno', 'op'=>'comprar', 'title'=>'Terreno Industrial', 'loc'=>'Querétaro, QRO', 'price'=>'$4,100,000', 'tag'=>'VENTA', 'img'=>'casa 1.mp4', 'stats'=>'1,200 m²'],
];

$cardsHtml = "";
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

    $cardsHtml .= '
        <article class="property-card" data-type="'.$p['type'].'" data-operation="'.$p['op'].'" onclick="handleMoreInfo('.$p['id'].')">
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


// 3. NEW LEGAL ACCORDION DATA
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
                <div class="legal-content">
                    <p>Contrato preparatorio que asegura las condiciones, precio y plazos antes de la escrituración formal. Contiene cláusulas de arras para proteger a ambas partes.</p>
                    <a href="#" style="color:#c9a86a; font-weight:bold; text-decoration:none; display:inline-block; margin-top:10px;">Descargar Modelo PDF</a>
                </div>
            </details>

            <details class="legal-item">
                <summary class="legal-summary">Arrendamiento Seguro</summary>
                <div class="legal-content">
                    <p>Requisitos de contratación: depósito en garantía, fiador con propiedad o póliza jurídica. Se entrega inventario detallado del inmueble.</p>
                </div>
            </details>

            <details class="legal-item">
                <summary class="legal-summary">Auditoría Registral (RPP)</summary>
                <div class="legal-content">
                    <p>Revisión exhaustiva ante el Registro Público de la Propiedad para garantizar que el inmueble está libre de gravámenes, embargos o afectaciones.</p>
                </div>
            </details>

            <details class="legal-item">
                <summary class="legal-summary">Uso de Suelo y Terrenos</summary>
                <div class="legal-content">
                    <p>Verificación de uso de suelo (residencial, comercial, agrícola), medidas oficiales, colindancias y factibilidad de servicios básicos.</p>
                </div>
            </details>

            <details class="legal-item">
                <summary class="legal-summary">Protección de Datos y Privacidad</summary>
                <div class="legal-content">
                    <p>Tus datos personales y financieros están encriptados y se utilizan exclusivamente para formalizar tu operación inmobiliaria. No compartimos información con terceros.</p>
                </div>
            </details>

        </div>
    </div>
</section>
';

$styles = '
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
';


// 4. APPLY TO EXPLORAR.HTML
$html = file_get_contents('explorar.html');
// Replace property grid
$start = strpos($html, '<section class="properties-container">');
$end = strpos($html, '</section>', $start) + 10;
if($start !== false) {
    $newGrid = '<section class="properties-container"><div class="property-grid" id="propertyGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">' . "\n" . $cardsHtml . "\n    </div></section>";
    $html = substr_replace($html, $newGrid, $start, $end - $start);
}

// Replace legal
$lStart = strpos($html, '<section class="section legal"');
$lEnd = strpos($html, '</section>', $lStart) + 10;
if($lStart !== false) {
    $html = substr_replace($html, $styles . $legalHtml, $lStart, $lEnd - $lStart);
}
file_put_contents('explorar.html', $html);


// 5. APPLY TO INDEX.HTML
$idx = file_get_contents('index.html');
// Replace legal
$iStart = strpos($idx, '<section class="section legal"');
$iEnd = strpos($idx, '</section>', $iStart) + 10;
if($iStart !== false) {
    $idx = substr_replace($idx, $styles . $legalHtml, $iStart, $iEnd - $iStart);
}
file_put_contents('index.html', $idx);

echo "Refactor complete!";
?>

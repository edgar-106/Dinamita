<?php
$html = file_get_contents('index.html');

$oldHeader = '<div class="section-header" style="text-align: center; margin-bottom: 40px;">
        <div class="section-label">Catálogo</div>
        <h2 style="font-family: Georgia, serif; font-size: clamp(32px, 5vw, 48px); margin: 0; color: #172033;">Encuentra tu espacio ideal</h2>
    </div>';

$newHeader = '<div style="text-align: center; margin-bottom: 30px; display: flex; flex-direction: column; align-items: center;">
        <div class="section-label" style="margin-bottom: 8px;">Catálogo</div>
        <h2 style="font-family: Georgia, serif; font-size: clamp(36px, 4vw, 48px); margin: 0; color: #172033; letter-spacing: -0.5px;">Encuentra tu espacio ideal</h2>
    </div>';

$html = str_replace($oldHeader, $newHeader, $html);
file_put_contents('index.html', $html);
echo "Fixed header layout\n";
?>

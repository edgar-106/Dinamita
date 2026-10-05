<?php
$files = ['index.html', 'explorar.html', 'vender.html'];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $html = file_get_contents($file);
    
    // Pattern to match the spans inside the navbar logo
    $pattern = '/<span>\s*<span class="logo-name">INFONATEC<\/span>\s*<span class="logo-sub">INMOBILIARIA<\/span>\s*<\/span>/';
    
    // Check if it exists and remove it
    $newHtml = preg_replace($pattern, '', $html);
    
    // Wait, the splash screen in index.html also has:
    // <div class="intro-logo-name">INFONATEC</div>
    // <div class="intro-logo-sub">Inmobiliaria</div>
    // Does the user want that removed too? "en lo de la imagen solo quiero que aparezca la casa ya no el nombre ni nada"
    // The image specifically shows the navbar (horizontal layout, smaller icon, gold background). The splash screen is huge and vertical.
    // I'll stick to removing the navbar text.

    if ($html !== $newHtml) {
        file_put_contents($file, $newHtml);
        echo "Updated logo in $file\n";
    } else {
        echo "Pattern not found in $file\n";
    }
}
?>

<?php
function fix_logo_href($filename) {
    if (!file_exists($filename)) return;
    $html = file_get_contents($filename);
    
    // Replace <a href="#" onclick="openSplash(event)" class="logo"...>
    // Or <a href="#" class="logo" ... onclick="window.scrollTo...">
    // With <a href="index.html" class="logo" aria-label="...">
    
    $pattern = '/<a\s+[^>]*class="logo"[^>]*>/';
    
    $html = preg_replace($pattern, '<a href="index.html" class="logo" aria-label="INFONATEC Inmobiliaria - Inicio">', $html);
    
    file_put_contents($filename, $html);
    echo "Fixed logo href in $filename\n";
}

fix_logo_href('explorar.html');
fix_logo_href('vender.html');
?>

<?php
$files = ['index.html', 'explorar.html', 'vender.html'];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    
    $html = file_get_contents($file);
    
    // Remove from navbar
    $html = preg_replace('/<li><a href="[^"]*publicar[^"]*">Publicar<\/a><\/li>\s*/i', '', $html);
    $html = preg_replace('/<li><a href="vender\.html">Publicar<\/a><\/li>\s*/i', '', $html);
    
    // Remove from mobile menu
    $html = preg_replace('/<a href="[^"]*publicar[^"]*">Publicar<\/a>\s*/i', '', $html);
    $html = preg_replace('/<a href="vender\.html">Publicar<\/a>\s*/i', '', $html);
    
    // Remove from footer (might be <p><a href="...">Publicar</a></p>)
    $html = preg_replace('/<p><a href="[^"]*publicar[^"]*">Publicar<\/a><\/p>\s*/i', '', $html);
    $html = preg_replace('/<p><a href="vender\.html">Publicar<\/a><\/p>\s*/i', '', $html);
    
    file_put_contents($file, $html);
    echo "Removed 'Publicar' links from $file\n";
}
?>

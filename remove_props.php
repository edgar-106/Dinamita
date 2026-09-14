<?php
$file = 'index.html';
$html = file_get_contents($file);

$startMarker = '<!-- =========================================================
     PROPIEDADES — BUSCADOR Y CATÁLOGO
========================================================= -->';

$start = strpos($html, $startMarker);
if ($start !== false) {
    $end = strpos($html, '</section>', $start) + 10;
    
    // Remove the whole section
    $html = substr_replace($html, '', $start, $end - $start);
    
    // Remove the extra newlines left behind
    $html = preg_replace("/\n{3,}/", "\n\n", $html);
    
    // Remove navbar link
    $html = str_replace('<li><a href="#propiedades">Propiedades</a></li>' . "\n", '', $html);
    $html = str_replace('<li><a href="#propiedades">Propiedades</a></li>', '', $html);
    
    // Remove footer link
    $html = str_replace('<p><a href="#propiedades">Propiedades</a></p>' . "\n", '', $html);
    $html = str_replace('<p><a href="#propiedades">Propiedades</a></p>', '', $html);
    
    // Remove mobile menu link
    $html = str_replace('<a href="#propiedades">Propiedades</a>' . "\n", '', $html);
    $html = str_replace('<a href="#propiedades">Propiedades</a>', '', $html);
    
    file_put_contents($file, $html);
    echo "Properties section removed.\n";
} else {
    echo "Properties section not found.\n";
}
?>

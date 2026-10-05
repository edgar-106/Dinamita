<?php
$files = ['index.html', 'explorar.html'];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $html = file_get_contents($file);
    
    // Remove the old #publicar section
    $startMarker = '<!-- =========================================================
     PUBLICAR PROPIEDAD
========================================================= -->';
    
    $start = strpos($html, $startMarker);
    if ($start !== false) {
        $end = strpos($html, '</section>', $start) + 10;
        if ($end !== false) {
            $html = substr_replace($html, '', $start, $end - $start);
        }
    }
    
    // Update navbar links from #publicar or index.html#publicar to vender.html
    $html = str_replace('href="#publicar"', 'href="vender.html"', $html);
    $html = str_replace('href="index.html#publicar"', 'href="vender.html"', $html);
    
    file_put_contents($file, $html);
}
echo "Links and sections updated.\n";
?>

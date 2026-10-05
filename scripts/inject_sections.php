<?php
$explorar = file_get_contents('explorar.html');
$sections = file_get_contents('sections_to_copy.txt');

$footerPos = strpos($explorar, '<!-- =========================================================
     FOOTER');

if ($footerPos !== false) {
    // Insert sections before footer
    $newExplorar = substr($explorar, 0, $footerPos) . $sections . substr($explorar, $footerPos);
    
    // Update the nav links in explorar.html to point to the local sections
    $newExplorar = str_replace('href="index.html#asesor"', 'href="#asesor"', $newExplorar);
    $newExplorar = str_replace('href="index.html#publicar"', 'href="#publicar"', $newExplorar);
    $newExplorar = str_replace('href="index.html#legal"', 'href="#legal"', $newExplorar);
    
    file_put_contents('explorar.html', $newExplorar);
    echo "explorar.html updated successfully.\n";
} else {
    echo "Footer not found in explorar.html.\n";
}
?>

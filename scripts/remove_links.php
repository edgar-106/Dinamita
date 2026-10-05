<?php
$file = 'explorar.html';
$html = file_get_contents($file);

// Remove navbar link
$html = str_replace('<li><a href="index.html#propiedades">Propiedades</a></li>' . "\n", '', $html);
$html = str_replace('<li><a href="index.html#propiedades">Propiedades</a></li>', '', $html);

// Remove footer link
$html = str_replace('<p><a href="index.html#propiedades">Propiedades</a></p>' . "\n", '', $html);
$html = str_replace('<p><a href="index.html#propiedades">Propiedades</a></p>', '', $html);

// Remove mobile menu link
$html = str_replace('<a href="index.html#propiedades">Propiedades</a>' . "\n", '', $html);
$html = str_replace('<a href="index.html#propiedades">Propiedades</a>', '', $html);

file_put_contents($file, $html);
echo "Links removed from explorar.html.\n";
?>

<?php
$idx = file_get_contents('index.html');

// In navbar & mobile menu
$idx = str_replace('<a href="explorar.html">Explorar</a>', '<a href="#adquirir">Explorar</a>', $idx);
$idx = str_replace('<a href="#categorias">Explorar</a>', '<a href="#adquirir">Explorar</a>', $idx);

// In footer
$idx = str_replace('<a href="explorar.html">', '<a href="#adquirir">', $idx);

file_put_contents('index.html', $idx);
echo "Updated links in index.html\n";
?>

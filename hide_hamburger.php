<?php
$html = file_get_contents('vender.html');

$html = str_replace('<button
            class="nav-hamburger"
            id="hamburgerBtn"
            aria-label="Abrir menú de navegación"
            aria-expanded="false"
            aria-controls="mobileMenu"
        >', '<button
            class="nav-hamburger"
            id="hamburgerBtn"
            aria-label="Abrir menú de navegación"
            aria-expanded="false"
            aria-controls="mobileMenu"
            style="display: none;"
        >', $html);

file_put_contents('vender.html', $html);
echo "Hidden hamburger in vender.html\n";
?>

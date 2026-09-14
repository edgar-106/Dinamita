<?php
$html = file_get_contents('vender.html');

$patternMobile = '/<div class="mobile-menu"[^>]*>.*?<\/div>/s';
// Wait, the mobile-menu also contains the close button!
// <div class="mobile-menu" id="mobileMenu" ...>
//    <button class="mobile-menu-close"...>×</button>
//    <a href="#categorias">Explorar</a>
//    ...
// </div>

// We can just remove the <a> tags inside the mobile menu
// Since there's only one mobile menu:
if (preg_match($patternMobile, $html, $matches)) {
    $mobileMenuHtml = $matches[0];
    $newMobileMenuHtml = preg_replace('/<a href="[^"]*">.*?<\/a>/s', '', $mobileMenuHtml);
    $html = str_replace($mobileMenuHtml, $newMobileMenuHtml, $html);
    file_put_contents('vender.html', $html);
    echo "Removed links from mobile menu in vender.html\n";
} else {
    echo "No mobile menu found in vender.html (maybe it wasn't copied?).\n";
}
?>

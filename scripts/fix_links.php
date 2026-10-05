<?php
$html = file_get_contents('explorar.html');
$html = str_replace('href="#publicar"', 'href="index.html#publicar"', $html);
$html = str_replace('href="#legal"', 'href="index.html#legal"', $html);
$html = str_replace('href="#asesor"', 'href="index.html#asesor"', $html);
file_put_contents('explorar.html', $html);
echo 'Links fixed.';
?>

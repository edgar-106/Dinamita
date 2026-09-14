<?php
$c = file_get_contents('explorar.html');
$pos = strpos($c, '<a href="explorar.html">Explorar</a>');
echo substr($c, $pos - 200, 400);
?>

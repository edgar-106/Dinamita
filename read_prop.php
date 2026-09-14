<?php
$c = file_get_contents('explorar.html');
$start = strpos($c, '<article class="property-card"');
echo substr($c, $start, 500);
?>

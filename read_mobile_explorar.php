<?php
$c = file_get_contents('explorar.html');
$start = strpos($c, 'mobile-menu');
echo substr($c, $start, 500);
?>

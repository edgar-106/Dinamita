<?php
$c = file_get_contents('explorar.html');
$s = strpos($c, 'properties-container');
echo substr($c, $s, 500);
?>

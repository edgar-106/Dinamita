<?php
$c = file_get_contents('vender.html');
$start = strpos($c, '<nav class="navbar"');
echo substr($c, $start, 500);
?>

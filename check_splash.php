<?php
$c = file_get_contents('js/app.js');
$start = strpos($c, 'function closeSplash');
echo substr($c, $start, 500);
?>

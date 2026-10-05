<?php
$c = file_get_contents('js/app.js');
$start = strpos($c, 'window.closeSplash = function');
echo substr($c, $start, 500);
?>

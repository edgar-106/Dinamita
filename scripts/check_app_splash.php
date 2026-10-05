<?php
$c = file_get_contents('js/app.js');
$s = strpos($c, 'window.closeSplash = function(operation)');
echo substr($c, $s, 1000);
?>

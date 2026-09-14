<?php
$c = file_get_contents('explorar.html');
if (strpos($c, 'switchOperation') !== false) echo "JS injected.\n";
if (strpos($c, 'operation-tabs') !== false) echo "HTML injected.\n";
if (strpos($c, '.op-btn') !== false) echo "CSS injected.\n";
?>

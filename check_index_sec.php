<?php
$c = file_get_contents('index.html');
$s1 = strpos($c, 'id="categorias"');
if ($s1 !== false) echo "Has categorias\n";
$s2 = strpos($c, 'id="proceso"');
if ($s2 !== false) echo "Has proceso\n";
$s3 = strpos($c, 'id="legal"');
if ($s3 !== false) echo "Has legal\n";
?>

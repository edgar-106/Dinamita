<?php
$idx = file_get_contents('index.html');
$p = strpos($idx, 'proceso');
echo substr($idx, max(0, $p - 50), 200);
?>

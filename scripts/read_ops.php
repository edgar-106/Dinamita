<?php
$c = file_get_contents('explorar.html');
preg_match_all('/data-operation="([^"]+)"/', $c, $matches);
print_r(array_unique($matches[1]));
?>

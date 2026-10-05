<?php
$c = file_get_contents('index.html');
echo substr($c, strpos($c, 'mobile-menu'), 500);
?>

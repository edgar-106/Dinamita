<?php
$c = file_get_contents('index.html');
echo substr($c, strpos($c, '<div class="intro-actions">'), 500);
?>

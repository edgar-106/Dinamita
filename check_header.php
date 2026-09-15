<?php
$css = file_get_contents('css/styles.css');
$pos = strpos($css, '.section-header');
if ($pos !== false) echo substr($css, $pos, 400);
?>

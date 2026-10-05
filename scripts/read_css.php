<?php
$css = file_get_contents('css/styles.css');
$start = strpos($css, '.category-grid');
echo substr($css, $start, 300);
?>

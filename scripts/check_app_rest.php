<?php
$c = file_get_contents('js/app.js');
$s = strpos($c, "const categorias = document.getElementById('adquirir');");
echo substr($c, $s, 500);
?>

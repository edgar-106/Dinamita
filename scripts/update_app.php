<?php
$app = file_get_contents('js/app.js');
$app = str_replace("document.getElementById('categorias')", "document.getElementById('adquirir')", $app);
file_put_contents('js/app.js', $app);
echo "Updated app.js\n";
?>

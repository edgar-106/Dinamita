<?php
$idx = file_get_contents('index.html');
if (strpos($idx, 'categorias') !== false) {
    echo "Found 'categorias'\n";
}
if (strpos($idx, 'proceso') !== false) {
    echo "Found 'proceso'\n";
}
if (strpos($idx, 'explorar.html') !== false) {
    echo "Found 'explorar.html'\n";
}
?>

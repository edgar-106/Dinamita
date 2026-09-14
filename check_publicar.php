<?php
$files = ['index.html', 'explorar.html', 'vender.html'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $c = file_get_contents($file);
    if (stripos($c, '>Publicar<') !== false) {
        echo "Still found >Publicar< in $file\n";
    }
}
echo "Check complete.\n";
?>

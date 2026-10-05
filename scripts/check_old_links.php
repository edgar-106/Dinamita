<?php
$c = file_get_contents('index.html');
if (strpos($c, 'explorar.html') !== false) {
    echo "index.html STILL links to explorar.html\n";
} else {
    echo "index.html has NO links to explorar.html\n";
}
if (strpos($c, '#categorias') !== false) {
    echo "index.html STILL links to #categorias\n";
} else {
    echo "index.html has NO links to #categorias\n";
}
?>

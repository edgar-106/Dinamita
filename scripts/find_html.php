<?php
$c = file_get_contents('explorar.html');
$s = strpos($c, '<section class="properties-container"');
if ($s === false) {
    echo "No properties container found in HTML\n";
    $s2 = strpos($c, '<div class="property-grid"');
    if ($s2 !== false) echo "Found property-grid\n" . substr($c, $s2, 500);
} else {
    echo substr($c, $s, 1000);
}
?>

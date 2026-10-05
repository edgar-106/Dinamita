<?php
$html = file_get_contents('explorar.html');
if (strpos($html, 'properties-container') !== false) {
    echo "explorar.html has properties-container\n";
} else {
    echo "explorar.html DOES NOT have properties-container\n";
}
if (strpos($html, 'properties-grid') !== false) {
    echo "explorar.html has properties-grid\n";
}
?>

<?php
$c = file_get_contents('explorar.html');
if (strpos($c, 'js-prop-card') !== false) {
    echo "Has js-prop-card\n";
} else {
    echo "NO js-prop-card\n";
}
if (strpos($c, 'EXCLUSIVA') !== false) {
    echo "Has EXCLUSIVA tag\n";
}
?>

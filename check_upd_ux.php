<?php
$c = file_get_contents('update_ux.php');
if (strpos($c, 'explorar.html') !== false) {
    echo "update_ux.php updated explorar.html!\n";
} else {
    echo "update_ux.php did NOT update explorar.html.\n";
}
?>

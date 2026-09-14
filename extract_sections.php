<?php
$html = file_get_contents('index.html');
$start = strpos($html, '<!-- =========================================================
     ASESOR');
$end = strpos($html, '<!-- =========================================================
     FOOTER');
if ($start !== false && $end !== false) {
    file_put_contents('sections_to_copy.txt', substr($html, $start, $end - $start));
    echo "Sections copied successfully.\n";
} else {
    echo "Could not find sections.\n";
}
?>

<?php
$idx = file_get_contents('index.html');
$startP = strpos($idx, '<section class="section" id="proceso"');
if ($startP !== false) {
    $endP = strpos($idx, '</section>', $startP) + 10;
    $idx = substr_replace($idx, '', $startP, $endP - $startP);
    file_put_contents('index.html', $idx);
    echo "Removed proceso\n";
} else {
    echo "No proceso section found.\n";
}
?>

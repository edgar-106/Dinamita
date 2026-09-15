<?php
$idx = file_get_contents('index.html');
$pos = strpos($idx, 'Fácil y transparente');
if ($pos !== false) {
    $startP = strrpos(substr($idx, 0, $pos), '<section');
    $endP = strpos($idx, '</section>', $startP) + 10;
    $idx = substr_replace($idx, '', $startP, $endP - $startP);
    file_put_contents('index.html', $idx);
    echo "Removed facil y transparente\n";
} else {
    echo "Not found.\n";
}
?>

<?php
$html = file_get_contents('vender.html');

// We want to replace the whole <ul class="nav-links"...> ... </ul> with an empty <ul>
$pattern = '/<ul class="nav-links" role="list">.*?<\/ul>/s';
$replacement = '<ul class="nav-links" role="list"></ul>';

$newHtml = preg_replace($pattern, $replacement, $html);

if ($html !== $newHtml) {
    file_put_contents('vender.html', $newHtml);
    echo "Removed nav links from vender.html\n";
} else {
    echo "Could not find nav-links.\n";
}
?>

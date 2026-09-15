<?php
$c = file_get_contents('index.html');
preg_match_all('/<section[^>]*id="([^"]*)"[^>]*>/', $c, $matches);
print_r($matches[1]);
?>

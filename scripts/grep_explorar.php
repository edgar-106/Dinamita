<?php
$c = file_get_contents('explorar.html');
preg_match_all('/<a[^>]*>[^<]*Explorar[^<]*<\/a>/i', $c, $matches);
print_r($matches);
?>

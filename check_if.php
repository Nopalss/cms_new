<?php
$lines = file('D:/laragon/www/cms/includes/aside.php');
$depth = 0;
foreach ($lines as $i => $line) {
    $n = $i + 1;
    preg_match_all('/<\?php\s+if\s*\(/i', $line, $opens);
    preg_match_all('/<\?php\s+endif\s*;/i', $line, $closes);
    $o = count($opens[0]);
    $c = count($closes[0]);
    $depth += $o - $c;
    if ($n >= 595 && $n <= 715) {
        echo "$n: depth=$depth opens=$o closes=$c\n";
    }
}

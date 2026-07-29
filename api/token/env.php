<?php

$env = array();

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die(".env file tidak ditemukan.");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {

    $line = trim($line);

    // Lewati baris kosong
    if ($line == '') {
        continue;
    }

    // Lewati komentar
    if (strpos($line, '#') === 0) {
        continue;
    }

    // Pastikan ada tanda "="
    if (strpos($line, '=') === false) {
        continue;
    }

    list($key, $value) = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value);

    $env[$key] = $value;
}

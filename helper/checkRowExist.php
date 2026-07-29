<?php
require __DIR__ . '/redirect.php';

function checkRowExist($row, $url)
{
    if (!$row) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Data Tidak Ditemukan',
            'text' => 'Silakan coba lagi.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect($url);
    }
}

<?php

function validatePhone($phone)
{
    // Hilangkan spasi, strip, atau karakter non-digit
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Validasi nomor Indonesia
    // Harus mulai dengan 08, panjang 10–13 digit
    if (preg_match('/^08[0-9]{8,11}$/', $phone)) {
        return true;
    }

    // Alternatif: jika pakai kode negara (+62)
    if (preg_match('/^62[0-9]{9,12}$/', $phone)) {
        return true;
    }

    return false; // selain itu dianggap tidak valid
}

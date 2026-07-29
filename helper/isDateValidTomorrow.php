<?php

function isDateValidTomorrow($dateInput)
{
    // Pastikan format Y-m-d
    $d = DateTime::createFromFormat('Y-m-d', $dateInput);
    if (!$d || $d->format('Y-m-d') !== $dateInput) {
        return false; // Format salah
    }

    $tomorrow = new DateTime('tomorrow');
    return $d >= $tomorrow;
}

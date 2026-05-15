<?php


function printLeftRight($print, $label, $value, $width)
{
    $label = (string) $label;
    $value = (string) $value;

    // Gunakan mb_strlen agar lebih aman untuk UTF-8
    $labelLength = mb_strlen($label, 'UTF-8');
    $valueLength = mb_strlen($value, 'UTF-8');

    // Hitung jumlah spasi di antara label dan value
    $spaces = $width - $labelLength - $valueLength;

    // Minimal 1 spasi
    if ($spaces < 1) {
        $spaces = 1;
    }

    // Cetak: LABEL.....VALUE
    $print->text(
        $label .
        str_repeat(' ', $spaces) .
        $value . "\n"
    );
}


?>
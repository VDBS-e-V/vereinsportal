<?php

namespace App\Support;

/** Schützt Tabellenprogramme vor Formeln in CSV-Zellen (Zellen mit = + - @ oder Tab beginnen mit einem Hochkomma). */
final class CsvCell
{
    public static function safe(mixed $value): string
    {
        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}

<?php

namespace App\Modules\Seminars\Support;

/** Beträge werden in Cent gerechnet und gespeichert; hier nur die Darstellung und das Einlesen. */
final class Money
{
    public static function format(int|float $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }

    /** Eingabe in Euro („32,50“ oder „32.5“, ohne Tausendertrennzeichen) als Cent, null bei ungültiger Eingabe. */
    public static function parseEuro(string $input): ?int
    {
        $input = str_replace([' ', '€'], '', trim($input));

        if (preg_match('/^\d{1,6}([.,]\d{1,2})?$/', $input) !== 1) {
            return null;
        }

        return (int) round(((float) str_replace(',', '.', $input)) * 100);
    }

    /** Cent als Eingabewert für ein Formularfeld („32,50“). */
    public static function toInput(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }
}

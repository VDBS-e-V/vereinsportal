<?php

namespace App\Modules\Forms\Services;

/** Prüfung und Darstellung von IBAN (Prüfsumme nach mod 97). */
final class Iban
{
    public static function normalize(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', $iban) ?? '');
    }

    public static function valid(string $iban): bool
    {
        $iban = self::normalize($iban);

        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban) !== 1) {
            return false;
        }

        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $digits = '';

        foreach (str_split($rearranged) as $char) {
            $digits .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        $remainder = 0;

        foreach (str_split($digits, 7) as $chunk) {
            $remainder = (int) (($remainder.$chunk) % 97);
        }

        return $remainder === 1;
    }

    /** Gruppiert in Viererblöcke („DE89 3704 …“). */
    public static function pretty(string $iban): string
    {
        return trim(implode(' ', str_split(self::normalize($iban), 4)));
    }
}

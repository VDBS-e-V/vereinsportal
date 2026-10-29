<?php

namespace App\Modules\Access\Services;

/**
 * Hash für Einmalcodes (E-Mail-Code, Wiederherstellungscodes). Gespeichert wird nur der HMAC über den
 * normalisierten Code mit dem APP_KEY; wer nur die Datenbank besitzt, kann Codes nicht durchprobieren.
 */
final class CodeHasher
{
    public static function hash(string $code): string
    {
        return hash_hmac('sha256', self::normalize($code), (string) config('app.key'));
    }

    /** Großbuchstaben und Ziffern, ohne Trenner und Leerzeichen. */
    public static function normalize(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }
}

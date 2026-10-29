<?php

namespace App\Modules\Access\Services;

/**
 * Zeitbasierte Einmalcodes (TOTP, RFC 6238) auf Basis von HMAC-SHA1, wie sie Authenticator-Apps erzeugen.
 *
 * Reine Funktionen ohne Datenbank und ohne Framework; die Secrets sind Base32-Text (RFC 4648, ohne Padding).
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const PERIOD = 30;

    public const DIGITS = 6;

    /** Erzeugt ein neues Secret (Standard: 160 Bit, als 32 Zeichen Base32). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /** Der Code zum angegebenen Zeitpunkt (Unix-Zeit, Standard: jetzt). */
    public static function code(string $secret, ?int $time = null, int $period = self::PERIOD, int $digits = self::DIGITS): string
    {
        return self::hotp($secret, intdiv($time ?? time(), $period), $digits);
    }

    /**
     * Prüft einen Code mit Toleranz für Uhrenabweichung (window = Zeitfenster davor und danach).
     * Gibt den Zählerwert des passenden Zeitfensters zurück (zum Sperren wiederverwendeter Codes) oder null.
     */
    public static function verify(string $secret, string $code, ?int $time = null, int $window = 1, int $period = self::PERIOD, int $digits = self::DIGITS): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== $digits) {
            return null;
        }

        $counter = intdiv($time ?? time(), $period);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if ($counter + $offset >= 0 && hash_equals(self::hotp($secret, $counter + $offset, $digits), $code)) {
                return $counter + $offset;
            }
        }

        return null;
    }

    /** otpauth-Adresse zum Einrichten per Link oder QR-Code. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Das Secret in Vierergruppen, leichter abzutippen. */
    public static function format(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    public static function base32Encode(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $output;
    }

    public static function base32Decode(string $text): string
    {
        $text = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text) ?? '');
        $bits = '';

        foreach (str_split($text) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }

    private static function hotp(string $secret, int $counter, int $digits): string
    {
        $hash = hash_hmac('sha1', pack('J', $counter), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }
}

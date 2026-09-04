<?php

namespace App\Modules\Identity\Services;

final class TotpService
{
    private const ALPHABET =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        return $this->base32Encode(
            random_bytes(20)
        );
    }

    public function provisioningUri(
        string $account,
        string $secret,
        string $issuer = 'VDB',
    ): string {
        $label = rawurlencode(
            $issuer.':'.$account
        );

        return 'otpauth://totp/'
            .$label
            .'?secret='.rawurlencode($secret)
            .'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1'
            .'&digits=6'
            .'&period=30';
    }

    public function verify(
        string $secret,
        string $code,
        ?int $timestamp = null,
    ): bool {
        $code = trim($code);

        if (
            preg_match(
                '/^\d{6}$/',
                $code,
            ) !== 1
        ) {
            return false;
        }

        $timestamp ??= time();

        $counter = intdiv(
            $timestamp,
            30,
        );

        /*
         * Übliche kleine Toleranz:
         * vorheriges, aktuelles und nächstes Fenster.
         */
        foreach ([-1, 0, 1] as $offset) {
            $expected = $this->codeForCounter(
                $secret,
                $counter + $offset,
            );

            if (
                hash_equals(
                    $expected,
                    $code,
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function codeForCounter(
        string $secret,
        int $counter,
    ): string {
        $binarySecret =
            $this->base32Decode($secret);

        $high = ($counter >> 32)
            & 0xFFFFFFFF;

        $low = $counter
            & 0xFFFFFFFF;

        $counterBytes = pack(
            'N2',
            $high,
            $low,
        );

        $hash = hash_hmac(
            'sha1',
            $counterBytes,
            $binarySecret,
            true,
        );

        $offset =
            ord($hash[19]) & 0x0F;

        $binary =
            (
                (ord($hash[$offset]) & 0x7F)
                << 24
            )
            | (
                (ord($hash[$offset + 1]) & 0xFF)
                << 16
            )
            | (
                (ord($hash[$offset + 2]) & 0xFF)
                << 8
            )
            | (
                ord($hash[$offset + 3])
                & 0xFF
            );

        return str_pad(
            (string) ($binary % 1_000_000),
            6,
            '0',
            STR_PAD_LEFT,
        );
    }

    private function base32Encode(
        string $data,
    ): string {
        $bits = '';

        foreach (
            str_split($data) as $character
        ) {
            $bits .= str_pad(
                decbin(ord($character)),
                8,
                '0',
                STR_PAD_LEFT,
            );
        }

        $encoded = '';

        foreach (
            str_split($bits, 5) as $chunk
        ) {
            $chunk = str_pad(
                $chunk,
                5,
                '0',
                STR_PAD_RIGHT,
            );

            $encoded .= self::ALPHABET[
                bindec($chunk)
            ];
        }

        return $encoded;
    }

    private function base32Decode(
        string $encoded,
    ): string {
        $encoded = strtoupper(
            preg_replace(
                '/[^A-Z2-7]/',
                '',
                $encoded,
            ) ?? ''
        );

        $bits = '';

        foreach (
            str_split($encoded) as $character
        ) {
            $position = strpos(
                self::ALPHABET,
                $character,
            );

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(
                decbin($position),
                5,
                '0',
                STR_PAD_LEFT,
            );
        }

        $decoded = '';

        foreach (
            str_split($bits, 8) as $chunk
        ) {
            if (strlen($chunk) !== 8) {
                continue;
            }

            $decoded .= chr(
                bindec($chunk)
            );
        }

        return $decoded;
    }
}

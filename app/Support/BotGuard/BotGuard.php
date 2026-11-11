<?php

namespace App\Support\BotGuard;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Schutz öffentlicher Formulare vor automatisierten Eingaben (Skripte, Massenbots, KI-Agenten).
 *
 * Selbst gehostet und ohne Drittanbieter. Jedes Formular trägt
 *  1. ein signiertes Token mit Ausgabezeit (HMAC mit dem App-Schlüssel),
 *  2. ein für Menschen unsichtbares Köderfeld (Honeypot),
 *  3. eine kleine Rechenaufgabe (Proof-of-Work), die der Browser im Hintergrund löst.
 * Geprüft werden Signatur, Alter (Mindest-Ausfüllzeit und Höchstalter), leeres Köderfeld, Rechenaufgabe und
 * Einmaligkeit des Tokens. Die Prüfung ersetzt weder Rate-Limits noch Server-Validierung, sie kommt zusätzlich.
 */
class BotGuard
{
    public const TOKEN_FIELD = '_bg_token';

    public const PROOF_FIELD = '_bg_proof';

    public const HONEYPOT_FIELD = 'homepage_url';

    public function enabled(): bool
    {
        return (bool) config('portal.bot_guard.enabled', true);
    }

    public function bits(): int
    {
        return max(0, min(24, (int) config('portal.bot_guard.difficulty_bits', 16)));
    }

    /** Neues Token: base64url(JSON{t, n, b}) . '.' . HMAC. */
    public function issue(): string
    {
        $payload = $this->encode(json_encode(['t' => time(), 'n' => bin2hex(random_bytes(8)), 'b' => $this->bits()], JSON_THROW_ON_ERROR));

        return $payload.'.'.$this->sign($payload);
    }

    /** Liefert null bei Erfolg, sonst einen kurzen Ablehnungsgrund (nur für das Protokoll, nie für die Anzeige). */
    public function failure(Request $request): ?string
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return 'honeypot';
        }

        $token = (string) $request->input(self::TOKEN_FIELD, '');
        [$payload, $signature] = array_pad(explode('.', $token, 2), 2, '');

        if ($payload === '' || ! hash_equals($this->sign($payload), $signature)) {
            return 'token';
        }

        $data = json_decode((string) $this->decode($payload), true);

        if (! is_array($data) || ! isset($data['t'], $data['b'])) {
            return 'token';
        }

        $age = time() - (int) $data['t'];

        if ($age < (int) config('portal.bot_guard.min_seconds', 2)) {
            return 'too_fast';
        }

        if ($age > (int) config('portal.bot_guard.max_seconds', 7200)) {
            return 'expired';
        }

        if (config('portal.bot_guard.proof_of_work', true) && ! $this->proofValid($token, (string) $request->input(self::PROOF_FIELD, ''), (int) $data['b'])) {
            return 'proof';
        }

        // Jedes Token gilt nur einmal.
        if (! Cache::add('bot-guard:'.hash('sha256', $token), true, (int) config('portal.bot_guard.max_seconds', 7200))) {
            return 'replay';
        }

        return null;
    }

    /** sha256(token:proof) muss mit mindestens $bits Nullbits beginnen. */
    public function proofValid(string $token, string $proof, int $bits): bool
    {
        if ($proof === '' || strlen($proof) > 20 || ! ctype_digit($proof)) {
            return false;
        }

        return $this->leadingZeroBits(hash('sha256', $token.':'.$proof, true)) >= $bits;
    }

    public function leadingZeroBits(string $binary): int
    {
        $count = 0;

        foreach (str_split($binary) as $byte) {
            $value = ord($byte);

            if ($value === 0) {
                $count += 8;

                continue;
            }

            return $count + (8 - strlen(decbin($value)));
        }

        return $count;
    }

    private function sign(string $payload): string
    {
        return $this->encode(hash_hmac('sha256', $payload, (string) config('app.key'), true));
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): string|false
    {
        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}

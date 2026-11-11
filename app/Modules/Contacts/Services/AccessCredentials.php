<?php

namespace App\Modules\Contacts\Services;

use App\Modules\Contacts\Models\AccessCredential;
use App\Modules\Contacts\Models\TeamContact;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Persönliche Zugänge: ein langer Link (Token) und ein kurzer Code zum Eintippen. Beides gibt es nur im Moment der Erzeugung im Klartext;
 * gespeichert sind Prüfsummen. Der Code wird nur zusammen mit der E-Mail-Adresse geprüft und ist gedrosselt (5 Fehlversuche je Adresse und Stunde).
 */
final class AccessCredentials
{
    /** Ohne leicht verwechselbare Zeichen (0/O, 1/I). */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const MAX_FAILURES = 5;

    private const BLOCK_SECONDS = 3600;

    /**
     * Erzeugt einen neuen Zugang. Frühere Zugänge der Adresse gelten noch die eingestellte Zeit weiter (Standard 14 Tage), damit eine alte Mail nicht sofort ins Leere läuft.
     *
     * @return array{credential: AccessCredential, token: string, code: string, link: string}
     */
    public function issue(TeamContact $contact, bool $revokeOld = false): array
    {
        $token = Str::random(40);
        $code = $this->newCode();

        $contact->credentials()->valid()->get()->each(function (AccessCredential $old) use ($revokeOld): void {
            if ($revokeOld) {
                $old->forceFill(['revoked_at' => now()])->save();
            } elseif ($old->expires_at === null || $old->expires_at->isAfter(now()->addDays($this->graceDays()))) {
                $old->forceFill(['expires_at' => now()->addDays($this->graceDays())])->save();
            }
        });

        $credential = $contact->credentials()->create([
            'token_hash' => $this->hash($token),
            'code_hash' => $this->hash($code),
            'code_hint' => substr($code, -2),
        ]);

        return ['credential' => $credential, 'token' => $token, 'code' => $this->formatCode($code), 'link' => route('access.link', ['token' => $token])];
    }

    /** Gültiger Zugang zu diesem Link, sonst null. */
    public function findByToken(string $token): ?AccessCredential
    {
        $credential = AccessCredential::query()->valid()->where('token_hash', $this->hash($token))->with('contact')->first();

        return $credential !== null && hash_equals($credential->token_hash, $this->hash($token)) ? $credential : null;
    }

    /** Gültiger Zugang zu Adresse und Code, sonst null. Zu viele Fehlversuche sperren die Adresse für eine Stunde. */
    public function findByCode(string $email, string $code): ?AccessCredential
    {
        $key = 'contact-code:'.sha1(mb_strtolower(trim($email)));

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILURES)) {
            return null;
        }

        $contact = TeamContact::query()->where('email', mb_strtolower(trim($email)))->first();
        $hash = $this->hash($this->normalizeCode($code));
        $credential = $contact?->credentials()->valid()->where('code_hash', $hash)->first();

        if ($credential === null) {
            RateLimiter::hit($key, self::BLOCK_SECONDS);

            return null;
        }

        RateLimiter::clear($key);
        $credential->setRelation('contact', $contact);

        return $credential;
    }

    public function isBlocked(string $email): bool
    {
        return RateLimiter::tooManyAttempts('contact-code:'.sha1(mb_strtolower(trim($email))), self::MAX_FAILURES);
    }

    public function touch(AccessCredential $credential): void
    {
        $credential->forceFill(['last_used_at' => now(), 'uses' => $credential->uses + 1])->save();
    }

    public function revokeAll(TeamContact $contact): int
    {
        return $contact->credentials()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    private function newCode(): string
    {
        $code = '';

        for ($i = 0; $i < 10; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }

    private function formatCode(string $code): string
    {
        return substr($code, 0, 5).'-'.substr($code, 5);
    }

    public function normalizeCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    private function graceDays(): int
    {
        return max(0, (int) config('portal.contacts.old_access_days', 14));
    }
}

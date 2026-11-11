<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailUnsubscribe;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Abmeldungen von Mitteilungen je Adresse und Thema. Der Abmeldelink enthält Adresse und Thema verschlüsselt und manipulationssicher (kein Konto nötig).
 * Für Teamende und Schulen mit Konto wirkt die Abmeldung auch in den Einstellungen des Kontos (notification_preferences) und umgekehrt.
 */
final class MailUnsubscribes
{
    /** Themen, bei denen es ein Konto mit Einstellung gibt. */
    private const ACCOUNT_TOPICS = ['team_mailings', 'school_mailings'];

    public function isUnsubscribed(string $email, string $topic): bool
    {
        return MailUnsubscribe::query()->where('email', mb_strtolower(trim($email)))->where('topic', $topic)->exists();
    }

    /**
     * Abgemeldete unter diesen Adressen, nach Thema.
     *
     * @param  list<string>  $emails
     * @return array<string, array<string, true>> Thema => [Adresse => true]
     */
    public function among(array $emails): array
    {
        $found = [];

        foreach (array_chunk(array_values(array_unique(array_map(fn (string $e): string => mb_strtolower(trim($e)), $emails))), 500) as $chunk) {
            foreach (MailUnsubscribe::query()->whereIn('email', $chunk)->get(['email', 'topic']) as $row) {
                $found[$row->topic][$row->email] = true;
            }
        }

        return $found;
    }

    public function unsubscribe(string $email, string $topic): void
    {
        $email = mb_strtolower(trim($email));

        MailUnsubscribe::query()->firstOrCreate(['email' => $email, 'topic' => $topic]);
        $this->syncAccount($email, $topic, false);
    }

    public function resubscribe(string $email, string $topic): void
    {
        $email = mb_strtolower(trim($email));

        MailUnsubscribe::query()->where('email', $email)->where('topic', $topic)->delete();
        $this->syncAccount($email, $topic, true);
    }

    /** Abmeldelink für diese Adresse und dieses Thema. */
    public function url(string $email, string $topic): string
    {
        return route('mailings.unsubscribe.show', ['token' => $this->token($email, $topic)]);
    }

    public function token(string $email, string $topic): string
    {
        return rtrim(strtr(base64_encode(Crypt::encryptString(json_encode([mb_strtolower(trim($email)), $topic], JSON_UNESCAPED_UNICODE))), '+/', '-_'), '=');
    }

    /** @return array{email: string, topic: string}|null null bei ungültigem oder verändertem Link */
    public function parse(string $token): ?array
    {
        $raw = base64_decode(strtr($token, '-_', '+/'), true);

        // Nur die ausgegebene Schreibweise gilt: angehängte oder veränderte Zeichen machen den Link ungültig
        if ($raw === false || rtrim(strtr(base64_encode($raw), '+/', '-_'), '=') !== $token) {
            return null;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($raw), true);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($decoded) || count($decoded) !== 2 || ! is_string($decoded[0]) || ! is_string($decoded[1])) {
            return null;
        }

        return ['email' => $decoded[0], 'topic' => $decoded[1]];
    }

    /** Adresse für die Anzeige verkürzt: a***@beispiel.example. */
    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).'***'.($domain !== '' ? '@'.$domain : '');
    }

    private function syncAccount(string $email, string $topic, bool $enabled): void
    {
        if (! in_array($topic, self::ACCOUNT_TOPICS, true)) {
            return;
        }

        $userId = User::query()->whereRaw('LOWER(email) = ?', [$email])->value('id');

        if ($userId === null) {
            return;
        }

        DB::table('notification_preferences')->updateOrInsert(['user_id' => $userId, 'key' => $topic], ['enabled' => $enabled, 'updated_at' => now(), 'created_at' => now()]);
    }
}

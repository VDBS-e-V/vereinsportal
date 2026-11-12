<?php

namespace App\Modules\Board\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Einstellungen zum Vorstandskalender: ein optionaler Link zu einem externen Kalender und der geheime Schlüssel der Abo-Adresse
 * (iCal-Feed). Kalenderprogramme melden sich nicht an, deshalb steckt der Schlüssel in der Adresse; er lässt sich jederzeit erneuern.
 */
final class BoardCalendar
{
    public function get(string $key): string
    {
        return (string) DB::table('board_settings')->where('key', $key)->value('value');
    }

    public function put(string $key, string $value): void
    {
        DB::table('board_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
    }

    public function externalUrl(): string
    {
        return $this->get('calendar_url');
    }

    public function saveExternalUrl(?string $url, User $by): void
    {
        $this->put('calendar_url', trim((string) $url));
        AuditLog::record(AuditEvent::BoardCalendarChanged, $by);
    }

    /** Einladungen per E-Mail sind abschaltbar und standardmäßig aus: Der Vorstand schaltet sie bewusst ein. */
    public function invitationsEnabled(): bool
    {
        return $this->get('invitations_enabled') === '1';
    }

    public function setInvitations(bool $enabled, User $by): void
    {
        $this->put('invitations_enabled', $enabled ? '1' : '0');
        AuditLog::record(AuditEvent::BoardCalendarChanged, $by, null, ['aktion' => $enabled ? 'Einladungen eingeschaltet' : 'Einladungen ausgeschaltet']);
    }

    /** Der Schlüssel wird beim ersten Bedarf erzeugt. */
    public function feedToken(): string
    {
        $token = $this->get('feed_token');

        if ($token === '') {
            $token = $this->renewToken();
        }

        return $token;
    }

    public function renewToken(?User $by = null): string
    {
        $token = Str::random(40);
        $this->put('feed_token', $token);

        if ($by instanceof User) {
            AuditLog::record(AuditEvent::BoardCalendarChanged, $by, null, ['aktion' => 'Abo-Adresse erneuert']);
        }

        return $token;
    }

    public function validToken(string $token): bool
    {
        $stored = $this->get('feed_token');

        return $stored !== '' && hash_equals($stored, $token);
    }

    public function feedUrl(): string
    {
        return route('board.feed', ['token' => $this->feedToken()]);
    }

    /** Abo-Adresse mit webcal:// statt https://, damit das Kalenderprogramm sie direkt öffnet. */
    public static function webcal(string $url): string
    {
        return preg_replace('#^https?://#i', 'webcal://', $url) ?? $url;
    }
}

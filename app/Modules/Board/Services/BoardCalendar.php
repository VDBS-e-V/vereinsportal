<?php

namespace App\Modules\Board\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Support\Facades\DB;

/**
 * Gemeinsamer Kalender des Vorstands für die Vorstandssitzungen: ein Link zum Kalender (zum Öffnen), optional ein
 * Abo-Link (iCal-Adresse zum Einbinden in das eigene Kalenderprogramm) und ein Hinweistext (zum Beispiel Sitzungsrhythmus).
 */
final class BoardCalendar
{
    public const KEYS = ['calendar_url', 'feed_url', 'notes'];

    /** @return array{calendar_url: string, feed_url: string, notes: string} */
    public function all(): array
    {
        $rows = DB::table('board_settings')->whereIn('key', self::KEYS)->pluck('value', 'key');

        return ['calendar_url' => (string) ($rows['calendar_url'] ?? ''), 'feed_url' => (string) ($rows['feed_url'] ?? ''), 'notes' => (string) ($rows['notes'] ?? '')];
    }

    /** @param  array<string, string|null>  $values */
    public function save(array $values, User $by): void
    {
        foreach (self::KEYS as $key) {
            DB::table('board_settings')->updateOrInsert(['key' => $key], ['value' => trim((string) ($values[$key] ?? '')), 'updated_at' => now()]);
        }

        AuditLog::record(AuditEvent::BoardCalendarChanged, $by);
    }

    /** Abo-Adresse mit webcal:// statt https://, damit das Kalenderprogramm sie direkt öffnet. */
    public static function webcal(string $url): string
    {
        return preg_replace('#^https://#i', 'webcal://', $url) ?? $url;
    }
}

<?php

namespace App\Modules\Forms\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Support\Facades\DB;

/** Einstellbare Werte der Anträge (Cent bzw. Zahlen), mit Standardwerten. Pflege durch die Geschäftsstelle. */
final class FormSettings
{
    public const DEFAULTS = [
        'approval_limit_cents' => 5000,   // bis zu diesem Betrag gibt die Geschäftsstelle allein frei
        'km_rate_cents' => 20,             // Kilometersatz für Fahrten mit dem eigenen Auto (Bundesreisekostengesetz § 5: 20 Cent je km)
        'car_max_cents' => 13000,          // Höchstbetrag der Wegstreckenentschädigung je Fahrt (BRKG § 5: 130 Euro, 0 = ohne Grenze)
        'max_km_per_trip' => 1000,         // Plausibilitätsgrenze je Fahrt
    ];

    public const LABELS = [
        'approval_limit_cents' => 'Freigabe allein durch die Geschäftsstelle bis (Euro)',
        'km_rate_cents' => 'Kilometersatz (Euro je km)',
        'car_max_cents' => 'Höchstbetrag je Fahrt mit dem eigenen Auto (Euro, 0 = ohne Grenze)',
        'max_km_per_trip' => 'Warnung ab Kilometern je Fahrt',
    ];

    public function int(string $key): int
    {
        $value = DB::table('form_settings')->where('key', $key)->value('value');

        return $value !== null ? (int) $value : (int) (self::DEFAULTS[$key] ?? 0);
    }

    public function approvalLimit(): int
    {
        return $this->int('approval_limit_cents');
    }

    /** @param  array<string, int>  $values */
    public function save(array $values, User $by): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            DB::table('form_settings')->updateOrInsert(['key' => $key], ['value' => (string) max(0, $value), 'updated_at' => now()]);
        }

        AuditLog::record(AuditEvent::FormSettingsChanged, $by);
    }
}

<?php

namespace App\Modules\Contracts\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Support\Facades\DB;

/** Vereinsdaten für den Vertrag (Name, Vorsitz, Anschrift, Ort). Der Vorstand ändert sie hier, nicht im Code. */
final class ContractSettings
{
    public const DEFAULTS = [
        'club_name' => 'Verband für Demokratiebildung und Bibliotheken an Schulen e.V.',
        'club_short' => 'VDBS e.V.',
        'chair' => 'Jan Ole Schmiedecke',
        'address' => 'Fritz-Karsen-Schule, Onkel-Bräsig-Straße 76, 12359 Berlin',
        'place' => 'Berlin',
    ];

    public const LABELS = [
        'club_name' => 'Name des Vereins (Auftraggeber, ausgeschrieben)',
        'club_short' => 'Kurzname (für die Unterschrift)',
        'chair' => 'Vorsitz',
        'address' => 'Anschrift des Vereins',
        'place' => 'Ort für die Vertragsunterschrift',
    ];

    public function get(string $key): string
    {
        $value = DB::table('contract_settings')->where('key', $key)->value('value');

        return $value !== null && $value !== '' ? (string) $value : (self::DEFAULTS[$key] ?? '');
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return collect(self::DEFAULTS)->mapWithKeys(fn ($default, $key): array => [$key => $this->get($key)])->all();
    }

    /** @param  array<string, string>  $values */
    public function save(array $values, User $by): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                DB::table('contract_settings')->updateOrInsert(['key' => $key], ['value' => trim((string) $value), 'updated_at' => now()]);
            }
        }

        AuditLog::record(AuditEvent::ContractSettingsChanged, $by);
    }
}

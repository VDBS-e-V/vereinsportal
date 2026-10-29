<?php

namespace App\Modules\Partners\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Support\PartnerData;
use Illuminate\Support\Str;

/** Schulen als Partner anlegen, ändern und löschen (jeweils mit Eintrag im Protokoll). */
final class PartnerAdministration
{
    /** @param  array<string, mixed>  $data  validierte Formulardaten (PartnerData::rules) */
    public function create(array $data, User $by): Partner
    {
        $partner = Partner::query()->create(PartnerData::columns($data) + [
            'public_id' => (string) Str::ulid(),
            'type' => PartnerType::School,
            'created_by_user_id' => $by->id,
            'updated_by_user_id' => $by->id,
        ]);

        AuditLog::record(AuditEvent::PartnerCreated, $by, $partner);

        return $partner;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Partner $partner, array $data, User $by): Partner
    {
        $partner->fill(PartnerData::columns($data) + ['updated_by_user_id' => $by->id])->save();

        $changed = array_values(array_diff(array_keys($partner->getChanges()), ['updated_at', 'updated_by_user_id']));

        if ($changed !== []) {
            AuditLog::record(AuditEvent::PartnerUpdated, $by, $partner, ['felder' => $changed]);
        }

        return $partner;
    }

    public function delete(Partner $partner, User $by): void
    {
        AuditLog::record(AuditEvent::PartnerDeleted, $by, $partner);

        $partner->delete();
    }

    /** Gibt es schon eine Schule mit diesem Namen am selben Ort (ohne Beachtung der Groß-/Kleinschreibung)? */
    public function exists(string $name, ?string $city, ?int $exceptId = null): bool
    {
        return Partner::query()
            ->where('type', PartnerType::School->value)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where(fn ($query) => filled($city)
                ? $query->whereRaw('LOWER(city) = ?', [mb_strtolower(trim((string) $city))])
                : $query->whereNull('city'))
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }
}

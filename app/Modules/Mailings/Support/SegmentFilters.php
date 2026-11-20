<?php

namespace App\Modules\Mailings\Support;

use App\Modules\Membership\Models\Membership;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\SchoolType;
use Illuminate\Support\Facades\DB;

/**
 * Die Filter der Verteiler. Jeder Filter liefert die E-Mail-Adressen (klein geschrieben), auf die er zutrifft; Filter sind UND-verknüpft,
 * mehrere Werte innerhalb eines Filters ODER-verknüpft. Eine Filterart ist eine kleine Methode, damit weitere leicht dazukommen.
 */
final class SegmentFilters
{
    /** Altersgruppen aus dem Geburtsdatum der Person: Schlüssel => [von, bis] (bis null = offen). */
    public const AGE_GROUPS = ['18-29' => [18, 29], '30-49' => [30, 49], '50-64' => [50, 64], '65+' => [65, null]];

    /** @return array<string, string> Schlüssel => Bezeichnung */
    public static function labels(): array
    {
        return [
            'federal_state' => 'Bundesland der Schule',
            'school_type' => 'Schulart',
            'onboarding' => 'Einarbeitung',
            'member_since' => 'Mitglied seit (Jahr)',
            'membership_status' => 'Mitgliedsstatus',
            'postal_prefix' => 'Postleitzahl beginnt mit',
            'age_group' => 'Altersgruppe',
            'contact_access' => 'Zugang der importierten Kontakte',
        ];
    }

    /** @return array<string, string> */
    public static function federalStates(): array
    {
        return collect(FederalState::cases())->mapWithKeys(fn (FederalState $state): array => [$state->value => $state->label()])->all();
    }

    /** @return array<string, string> */
    public static function schoolTypes(): array
    {
        return collect(SchoolType::cases())->mapWithKeys(fn (SchoolType $type): array => [$type->value => $type->label()])->all();
    }

    /**
     * Bereinigt die Eingabe: nur bekannte Filter mit sinnvollen Werten, leere fallen weg.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $clean = [];

        foreach (['federal_state' => array_keys(self::federalStates()), 'school_type' => array_keys(self::schoolTypes()), 'membership_status' => ['requested', 'active', 'ended', 'rejected'], 'age_group' => array_keys(self::AGE_GROUPS)] as $key => $allowed) {
            $values = array_values(array_intersect(array_map('strval', (array) ($input[$key] ?? [])), $allowed));

            if ($values !== []) {
                $clean[$key] = $values;
            }
        }

        if (in_array($input['contact_access'] ?? '', ['not_sent', 'sent_unused', 'used'], true)) {
            $clean['contact_access'] = $input['contact_access'];
        }

        foreach (['onboarding' => ['open', 'done']] as $key => $allowed) {
            if (in_array($input[$key] ?? '', $allowed, true)) {
                $clean[$key] = $input[$key];
            }
        }

        $from = filled($input['member_since_from'] ?? null) ? (int) $input['member_since_from'] : null;
        $to = filled($input['member_since_to'] ?? null) ? (int) $input['member_since_to'] : null;

        if ($from !== null || $to !== null) {
            $clean['member_since'] = ['from' => $from, 'to' => $to];
        }

        $prefixes = array_values(array_filter(array_map(fn (string $part): string => preg_replace('/\D/', '', $part) ?? '', explode(',', (string) ($input['postal_prefix'] ?? '')))));

        if ($prefixes !== []) {
            $clean['postal_prefix'] = $prefixes;
        }

        return $clean;
    }

    /**
     * E-Mail-Adressen (klein geschrieben), auf die der Filter zutrifft.
     *
     * @return list<string>
     */
    public static function emails(string $key, mixed $value): array
    {
        $emails = match ($key) {
            'federal_state' => self::schools('federal_state', (array) $value),
            'school_type' => self::schools('school_type', (array) $value),
            'onboarding' => self::userEmails(TeamOnboarding::query()->when($value === 'open', fn ($q) => $q->whereNull('completed_at'), fn ($q) => $q->whereNotNull('completed_at'))->pluck('user_id')->all()),
            'member_since' => self::memberSince((array) $value),
            'membership_status' => Membership::query()->whereIn('status', (array) $value)->with('person')->get()->map(fn (Membership $m): ?string => $m->contactEmail())->all(),
            'postal_prefix' => self::persons(fn ($query) => $query->where(function ($inner) use ($value): void {
                foreach ((array) $value as $prefix) {
                    $inner->orWhere('postal_code', 'like', $prefix.'%');
                }
            })),
            'age_group' => self::persons(fn ($query) => $query->where(function ($inner) use ($value): void {
                foreach ((array) $value as $group) {
                    [$from, $to] = self::AGE_GROUPS[$group] ?? [null, null];
                    $inner->orWhere(function ($range) use ($from, $to): void {
                        $range->where('birth_date', '<=', now()->subYears((int) $from)->toDateString());

                        if ($to !== null) {
                            $range->where('birth_date', '>', now()->subYears($to + 1)->toDateString());
                        }
                    });
                }
            })),
            'contact_access' => self::contactAccess((string) $value),
            default => [],
        };

        return array_values(array_unique(array_filter(array_map(fn ($email): string => mb_strtolower(trim((string) $email)), $emails))));
    }

    /** @param  list<string>  $values @return list<string> */
    private static function schools(string $attribute, array $values): array
    {
        $partners = DB::table('partners')->whereIn('attributes->'.$attribute, $values)->pluck('id')->all();

        if ($partners === []) {
            return [];
        }

        $contacts = DB::table('partner_contacts')->whereIn('partner_id', $partners)->whereNotNull('email')->pluck('email')->all();
        $accounts = DB::table('partner_users')->join('users', 'users.id', '=', 'partner_users.user_id')->whereIn('partner_users.partner_id', $partners)->pluck('users.email')->all();

        return [...$contacts, ...$accounts];
    }

    /** @param  array<string, mixed>  $range @return list<string> */
    private static function memberSince(array $range): array
    {
        $query = Membership::query()->whereNotNull('starts_on')->with('person');

        if (filled($range['from'] ?? null)) {
            $query->where('starts_on', '>=', ((int) $range['from']).'-01-01');
        }

        if (filled($range['to'] ?? null)) {
            $query->where('starts_on', '<=', ((int) $range['to']).'-12-31');
        }

        return $query->get()->map(fn (Membership $m): ?string => $m->contactEmail())->all();
    }

    /** E-Mail-Adressen von Personen und ihren Konten. @return list<string> */
    private static function persons(callable $scope): array
    {
        $query = DB::table('persons');
        $scope($query);
        $ids = $query->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        return [...DB::table('persons')->whereIn('id', $ids)->pluck('email')->all(), ...DB::table('users')->whereIn('person_id', $ids)->pluck('email')->all()];
    }

    /** Importierte Kontakte nach Stand des persönlichen Zugangs: noch nichts gesendet, gesendet aber nie benutzt, benutzt. @return list<string> */
    private static function contactAccess(string $state): array
    {
        $credentials = DB::table('access_credentials')->whereNull('revoked_at');
        $sent = (clone $credentials)->whereNotNull('sent_at')->pluck('contact_id')->unique()->all();
        $used = (clone $credentials)->whereNotNull('last_used_at')->pluck('contact_id')->unique()->all();

        $ids = match ($state) {
            'used' => $used,
            'sent_unused' => array_diff($sent, $used),
            default => DB::table('team_contacts')->whereNotIn('id', $sent ?: [0])->pluck('id')->all(),
        };

        return $ids === [] ? [] : DB::table('team_contacts')->whereIn('id', $ids)->pluck('email')->all();
    }

    /**
     * @param  list<int>  $ids
     * @param  list<int>  $without
     * @return list<string>
     */
    private static function userEmails(array $ids, array $without = []): array
    {
        $ids = array_values(array_diff($ids, $without));

        return $ids === [] ? [] : DB::table('users')->whereIn('id', $ids)->pluck('email')->all();
    }
}

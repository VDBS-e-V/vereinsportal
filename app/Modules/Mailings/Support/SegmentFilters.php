<?php

namespace App\Modules\Mailings\Support;

use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Membership\Models\Membership;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\SchoolType;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\TeamProfile;
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
            'seminar_id' => 'Seminar',
            'onboarding' => 'Einarbeitung',
            'member_since' => 'Mitglied seit (Jahr)',
            'membership_status' => 'Mitgliedsstatus',
            'postal_prefix' => 'Postleitzahl beginnt mit',
            'age_group' => 'Altersgruppe',
            'profile' => 'Teamprofil',
            'applied' => 'Bewerbung in den letzten 12 Monaten',
            'competence' => 'Kompetenzen im Teamprofil',
            'seminars_done' => 'Mindestens so viele durchgeführte Seminare',
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

        if (filled($input['seminar_id'] ?? null)) {
            $clean['seminar_id'] = (int) $input['seminar_id'];
            $clean['seminar_status'] = in_array($input['seminar_status'] ?? '', ['applied', 'confirmed'], true) ? $input['seminar_status'] : 'any';
        }

        $competences = array_values(array_intersect(array_map('strval', (array) ($input['competence'] ?? [])), array_keys((array) config('portal.team.competences', []))));

        if ($competences !== []) {
            $clean['competence'] = $competences;
        }

        if (filled($input['seminars_done'] ?? null) && (int) $input['seminars_done'] > 0) {
            $clean['seminars_done'] = min(99, (int) $input['seminars_done']);
        }

        foreach (['onboarding' => ['open', 'done'], 'profile' => ['complete', 'incomplete'], 'applied' => ['recent', 'never']] as $key => $allowed) {
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
    public static function emails(string $key, mixed $value, mixed $extra = null): array
    {
        $emails = match ($key) {
            'federal_state' => self::schools('federal_state', (array) $value),
            'school_type' => self::schools('school_type', (array) $value),
            'seminar_id' => self::seminar((int) $value, (string) $extra),
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
            'profile' => self::profile((string) $value),
            'applied' => self::applied((string) $value),
            'competence' => self::competence((array) $value),
            'seminars_done' => self::seminarsDone((int) $value),
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

    /** @return list<string> */
    private static function seminar(int $seminarId, string $status): array
    {
        $applications = SeminarApplication::query()->where('seminar_id', $seminarId)
            ->when(in_array($status, ['applied', 'confirmed'], true), fn ($query) => $query->where('status', $status), fn ($query) => $query->whereIn('status', ['applied', 'confirmed']))
            ->with(['user', 'contact'])->get();

        return $applications->map(fn (SeminarApplication $application): ?string => $application->user?->email ?? $application->contact?->email)->all();
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

    /** @return list<string> */
    private static function profile(string $state): array
    {
        $complete = TeamProfile::query()->get()->reject(fn (TeamProfile $profile): bool => $profile->isEmpty())->pluck('user_id')->all();

        if ($state === 'complete') {
            return self::userEmails($complete);
        }

        return self::userEmails(self::teamUserIds(), $complete);
    }

    /** @return list<string> */
    private static function applied(string $state): array
    {
        $recent = SeminarApplication::query()->whereNotNull('user_id')->where('created_at', '>=', now()->subMonths(12))->pluck('user_id')->unique()->all();

        if ($state === 'recent') {
            return self::userEmails($recent);
        }

        $ever = SeminarApplication::query()->whereNotNull('user_id')->pluck('user_id')->unique()->all();

        return self::userEmails(self::teamUserIds(), $ever);
    }

    /** Teamende, die mindestens eine der gewählten Kompetenzen angegeben haben. @param  list<string>  $wanted @return list<string> */
    private static function competence(array $wanted): array
    {
        $ids = TeamProfile::query()->get()->filter(fn (TeamProfile $profile): bool => array_intersect($wanted, (array) $profile->competences) !== [])->pluck('user_id')->all();

        return self::userEmails($ids);
    }

    /** Teamende mit mindestens n bestätigten Einsätzen in durchgeführten oder abgeschlossenen Seminaren. @return list<string> */
    private static function seminarsDone(int $minimum): array
    {
        $ids = SeminarApplication::query()->whereNotNull('user_id')->where('status', 'confirmed')
            ->whereHas('seminar', fn ($query) => $query->whereIn('status', ['done', 'completed']))
            ->selectRaw('user_id, count(*) as total')->groupBy('user_id')->havingRaw('count(*) >= ?', [max(1, $minimum)])->pluck('user_id')->all();

        return self::userEmails($ids);
    }

    /** @return list<int> */
    private static function teamUserIds(): array
    {
        return DB::table('role_assignments')->join('roles', 'roles.id', '=', 'role_assignments.role_id')->where('roles.key', RoleKey::Team->value)
            ->where(fn ($open) => $open->whereNull('role_assignments.ends_at')->orWhere('role_assignments.ends_at', '>', now()))->pluck('role_assignments.user_id')->unique()->all();
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

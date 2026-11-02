<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Support\MailGroups;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Database\Eloquent\Collection;

/**
 * Wer ein Schreiben bekommt. Je E-Mail-Adresse genau eine Nachricht. Mitglieder: alle laufenden Mitgliedschaften mit Adresse (Einladungen zu
 * Versammlungen sind Vereinspost und nicht abschaltbar). Teamende: aktive Konten mit der Rolle Team, die diese Mitteilungen nicht abbestellt haben.
 *
 * @phpstan-type Recipient array{email: string, first: string, last: string}
 */
final class MailRecipients
{
    public function __construct(private readonly NotificationPreferences $preferences, private readonly MailSuppressions $suppressions, private readonly MailUnsubscribes $unsubscribes) {}

    /** @return Collection<int, Membership> */
    private function activeMemberships(): Collection
    {
        return $this->activeMembershipQuery()->with('person')->get();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Membership> */
    private function activeMembershipQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()));
    }

    /**
     * Mitglieder, die ein Schreiben an die Gruppe Mitglieder nicht erreicht hat (laufende Mitgliedschaften, Stand heute): ohne gültige Adresse,
     * gesperrte Adresse, Mitteilungen abbestellt (außer bei Pflichtmitteilungen) oder Zustellung fehlgeschlagen oder übersprungen. Nur Namen und Grund.
     *
     * @return list<array{name: string, reason: string}>
     */
    public function unreachableMembers(Mailing $mailing): array
    {
        $memberships = $this->activeMemberships();
        $emails = [];

        foreach ($memberships as $membership) {
            $email = mb_strtolower(trim((string) $membership->contactEmail()));

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $emails[] = $email;
            }
        }

        $suppressed = $this->suppressions->among($emails);
        $unsubscribed = $mailing->mandatory ? [] : ($this->unsubscribes->among($emails)[MailGroups::topicFor('members') ?? ''] ?? []);
        $deliveries = $mailing->deliveries()->whereIn('status', ['failed', 'skipped'])->pluck('status', 'email')->mapWithKeys(fn ($status, $email): array => [mb_strtolower((string) $email) => $status])->all();
        $result = [];

        foreach ($memberships as $membership) {
            $email = mb_strtolower(trim((string) $membership->contactEmail()));
            $reason = match (true) {
                $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false => 'Keine gültige E-Mail-Adresse hinterlegt',
                isset($suppressed[$email]) => 'E-Mail-Adresse gesperrt',
                isset($unsubscribed[$email]) => 'Hat Mitteilungen abbestellt',
                ($deliveries[$email] ?? null) === 'failed' => 'Zustellung fehlgeschlagen',
                ($deliveries[$email] ?? null) === 'skipped' => 'Nicht zugestellt (übersprungen)',
                default => null,
            };

            if ($reason === null) {
                continue;
            }

            $first = trim((string) ($membership->person?->first_name ?? $membership->first_name ?? ''));
            $last = trim((string) ($membership->person?->last_name ?? $membership->last_name ?? ''));
            $result[] = ['name' => trim($first.' '.$last) !== '' ? trim($first.' '.$last) : 'Mitglied ohne Namen', 'reason' => $reason, 'sort' => mb_strtolower($last.' '.$first)];
        }

        usort($result, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(fn (array $row): array => ['name' => $row['name'], 'reason' => $row['reason']], $result);
    }

    /** @return list<array{email: string, first: string, last: string}> */
    public function members(): array
    {
        $memberships = $this->activeMemberships();

        $list = [];

        foreach ($memberships as $membership) {
            $list[] = [
                'email' => (string) $membership->contactEmail(),
                'first' => (string) ($membership->person?->first_name ?? $membership->first_name ?? ''),
                'last' => (string) ($membership->person?->last_name ?? $membership->last_name ?? ''),
                'fallback' => 'Mitglied',
                'group' => 'members',
            ];
        }

        return $this->unique($list);
    }

    /** @return list<array{email: string, first: string, last: string}> */
    public function team(?Seminar $seminar, string $audience): array
    {
        $users = match ($audience) {
            'applied' => $this->applicants($seminar, ApplicationStatus::Applied),
            'confirmed' => $this->applicants($seminar, ApplicationStatus::Confirmed),
            default => User::query()
                ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::Team->value)))
                ->with('person')
                ->get()
                ->all(),
        };

        $users = array_filter($users, fn (User $user): bool => $user->status === UserStatus::Active);
        $users = $this->preferences->filter($users, 'team_mailings');

        $list = $this->unique(array_map(fn (User $user): array => [
            'email' => (string) $user->email,
            'first' => (string) ($user->person?->first_name ?? ''),
            'last' => (string) ($user->person?->last_name ?? ''),
            'fallback' => 'Teamer:in',
            'group' => 'team',
        ], $users));

        return $this->filter($list, false)['kept'];
    }

    /**
     * Empfänger aus mehreren Gruppen, jede Adresse einmal (die erste Gruppe zählt). Rechenweg: Vereinigung der Gruppen minus Ausschlüsse (nach Adresse,
     * auch wenn die Person in der Ausschlussgruppe Mitteilungen abbestellt hat), dazu die einzeln gewählten Personen, danach Duplikate, Abmeldungen und Sperren.
     *
     * @param  list<string>  $groups
     * @param  list<string>  $exclude  Gruppen, deren Mitglieder nicht angeschrieben werden
     * @param  list<string>  $people  einzeln gewählte Personen (Marken wie u:12, m:5, k:7)
     * @return list<array{email: string, first: string, last: string, fallback: string, group: string}>
     */
    public function groups(array $groups, bool $mandatory = false, array $exclude = [], array $people = []): array
    {
        return $this->groupsWithStats($groups, $mandatory, $exclude, $people)['kept'];
    }

    /**
     * Wie groups(), mit den Zahlen der Ausgeschlossenen: Abgemeldete, Gesperrte und durch die Ausschlussgruppen Herausgenommene.
     *
     * @param  list<string>  $groups
     * @param  list<string>  $exclude
     * @param  list<string>  $people
     * @return array{kept: list<array<string, string>>, unsubscribed: int, suppressed: int, excluded: int}
     */
    public function groupsWithStats(array $groups, bool $mandatory = false, array $exclude = [], array $people = []): array
    {
        $list = $this->rawGroups($groups);
        $excluded = 0;

        if ($exclude !== []) {
            $blocked = array_flip(array_column($this->rawGroups($exclude, true), 'email'));
            $before = count($list);
            $list = array_values(array_filter($list, fn (array $entry): bool => ! isset($blocked[$entry['email']])));
            $excluded = $before - count($list);
        }

        if ($people !== []) {
            array_push($list, ...$this->people($people));
            $list = $this->unique($list);
        }

        return $this->filter($list, $mandatory) + ['excluded' => $excluded];
    }

    /**
     * Nimmt Gesperrte heraus (immer) und Abgemeldete (außer bei Pflichtmitteilungen).
     *
     * @param  list<array<string, string>>  $list
     * @return array{kept: list<array<string, string>>, unsubscribed: int, suppressed: int}
     */
    private function filter(array $list, bool $mandatory): array
    {
        if ($list === []) {
            return ['kept' => [], 'unsubscribed' => 0, 'suppressed' => 0];
        }

        $emails = array_column($list, 'email');
        $suppressed = $this->suppressions->among($emails);
        $unsubscribed = $mandatory ? [] : $this->unsubscribes->among($emails);
        $kept = [];
        $countSuppressed = 0;
        $countUnsubscribed = 0;

        foreach ($list as $entry) {
            $email = mb_strtolower($entry['email']);
            $topic = MailGroups::topicFor($entry['group'] ?? null);

            if (isset($suppressed[$email])) {
                $countSuppressed++;

                continue;
            }

            if ($topic !== null && isset($unsubscribed[$topic][$email])) {
                $countUnsubscribed++;

                continue;
            }

            $kept[] = $entry;
        }

        return ['kept' => $kept, 'unsubscribed' => $countUnsubscribed, 'suppressed' => $countSuppressed];
    }

    /**
     * @param  list<string>  $groups
     * @return list<array<string, string>>
     */
    private function rawGroups(array $groups, bool $ignoreOptout = false): array
    {
        $list = [];

        foreach ($groups as $key) {
            $definition = MailGroups::GROUPS[$key] ?? null;

            if ($definition === null) {
                continue;
            }

            if ($key === 'members') {
                array_push($list, ...$this->members());

                continue;
            }

            $users = User::query()
                ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', $definition['role'])))
                ->with('person')
                ->get()
                ->all();

            $users = array_filter($users, fn (User $user): bool => $user->status === UserStatus::Active);

            if ($definition['optout'] !== null && ! $ignoreOptout) {
                $users = $this->preferences->filter($users, $definition['optout']);
            }

            foreach ($users as $user) {
                $list[] = ['email' => (string) $user->email, 'first' => (string) ($user->person?->first_name ?? ''), 'last' => (string) ($user->person?->last_name ?? ''), 'fallback' => $definition['fallback'], 'group' => $key];
            }
        }

        return $this->unique($list);
    }

    /**
     * Einzeln gewählte Personen als Empfänger. Marken: u:ID (aktives Konto), m:ID (laufende Mitgliedschaft), k:ID (Ansprechperson einer Schule mit Adresse).
     * Direkt gewählte Personen bekommen das Schreiben unabhängig von Abmeldungen für Sammelmitteilungen, gesperrte Adressen aber nie.
     *
     * @param  list<string>  $tokens
     * @return list<array{email: string, first: string, last: string, fallback: string, group: string}>
     */
    public function people(array $tokens): array
    {
        return $this->unique(array_values(array_map(fn (array $row): array => $row['entry'], $this->resolve($tokens))));
    }

    /**
     * Namen zu den Marken für die Chips im Formular (nur gültige Marken, in der Reihenfolge der Eingabe).
     *
     * @param  list<string>  $tokens
     * @return list<array{token: string, label: string}>
     */
    public function describe(array $tokens): array
    {
        return array_values(array_map(fn (array $row): array => ['token' => $row['token'], 'label' => $row['label']], $this->resolve($tokens)));
    }

    /**
     * Marken, die diese Person wählen darf: Konten und Schulkontakte mit mailings.send, Mitgliedschaften nur mit mailings.members.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    public function allowedTokens(array $tokens, User $by): array
    {
        $allowed = [];

        foreach (array_unique(array_map('strval', $tokens)) as $token) {
            if (! preg_match('/^([umk]):\d+$/', $token, $match)) {
                continue;
            }

            if ($match[1] === 'm' ? $by->can('mailings.members') : $by->can('mailings.send')) {
                $allowed[] = $token;
            }
        }

        return $allowed;
    }

    /**
     * Marken der Ansprechpersonen einer Schule, die eine E-Mail-Adresse haben.
     *
     * @return list<string>
     */
    public function contactTokens(int $partnerId): array
    {
        return PartnerContact::query()->where('partner_id', $partnerId)->whereNotNull('email')->where('email', '!=', '')->orderBy('id')->pluck('id')->map(fn ($id): string => 'k:'.$id)->all();
    }

    /**
     * Suche nach Konten und Mitgliedern für die Einzelauswahl: jedes Wort muss im Vor- oder Nachnamen vorkommen. Höchstens je acht Treffer.
     *
     * @return list<array{token: string, label: string, hint: string}>
     */
    public function search(string $query, User $by): array
    {
        $words = array_values(array_filter(preg_split('/\s+/u', trim($query)) ?: [], fn (string $word): bool => mb_strlen($word) >= 2));

        if ($words === []) {
            return [];
        }

        $like = fn (string $word): string => '%'.addcslashes($word, '%_\\').'%';
        $result = [];

        if ($by->can('mailings.send')) {
            $users = User::query()->where('status', UserStatus::Active->value)->whereHas('person', function ($person) use ($words, $like): void {
                foreach ($words as $word) {
                    $person->where(fn ($inner) => $inner->where('first_name', 'like', $like($word))->orWhere('last_name', 'like', $like($word)));
                }
            })->with('person')->orderBy('id')->limit(8)->get();

            foreach ($users as $user) {
                $result[] = ['token' => 'u:'.$user->id, 'label' => (string) ($user->person?->fullName() ?? $user->email), 'hint' => 'Konto'];
            }
        }

        if ($by->can('mailings.members')) {
            $memberships = $this->activeMembershipQuery()->where(function ($outer) use ($words, $like): void {
                foreach ($words as $word) {
                    $outer->where(fn ($inner) => $inner->where('first_name', 'like', $like($word))->orWhere('last_name', 'like', $like($word))
                        ->orWhereHas('person', fn ($person) => $person->where('first_name', 'like', $like($word))->orWhere('last_name', 'like', $like($word))));
                }
            })->with('person')->orderBy('id')->limit(8)->get();

            foreach ($memberships as $membership) {
                $result[] = ['token' => 'm:'.$membership->id, 'label' => $this->membershipName($membership), 'hint' => 'Mitglied'];
            }
        }

        return $result;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<array{token: string, label: string, entry: array<string, string>}>
     */
    private function resolve(array $tokens): array
    {
        $ids = ['u' => [], 'm' => [], 'k' => []];

        foreach ($tokens as $token) {
            if (preg_match('/^([umk]):(\d+)$/', (string) $token, $match)) {
                $ids[$match[1]][] = (int) $match[2];
            }
        }

        $found = [];

        if ($ids['u'] !== []) {
            foreach (User::query()->whereIn('id', $ids['u'])->where('status', UserStatus::Active->value)->with('person')->get() as $user) {
                $found['u:'.$user->id] = ['label' => (string) ($user->person?->fullName() ?? $user->email), 'entry' => ['email' => (string) $user->email, 'first' => (string) ($user->person?->first_name ?? ''), 'last' => (string) ($user->person?->last_name ?? ''), 'fallback' => 'Kolleg:in', 'group' => 'direct']];
            }
        }

        if ($ids['m'] !== []) {
            foreach ($this->activeMembershipQuery()->whereIn('id', $ids['m'])->with('person')->get() as $membership) {
                $found['m:'.$membership->id] = ['label' => $this->membershipName($membership), 'entry' => ['email' => (string) $membership->contactEmail(), 'first' => (string) ($membership->person?->first_name ?? $membership->first_name ?? ''), 'last' => (string) ($membership->person?->last_name ?? $membership->last_name ?? ''), 'fallback' => 'Mitglied', 'group' => 'direct']];
            }
        }

        if ($ids['k'] !== []) {
            foreach (PartnerContact::query()->whereIn('id', $ids['k'])->whereNotNull('email')->where('email', '!=', '')->get() as $contact) {
                $name = trim((string) $contact->name);
                [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');
                $found['k:'.$contact->id] = ['label' => $name !== '' ? $name : (string) $contact->email, 'entry' => ['email' => (string) $contact->email, 'first' => $first, 'last' => $last, 'fallback' => 'Ansprechperson', 'group' => 'direct']];
            }
        }

        $ordered = [];

        foreach (array_unique(array_map('strval', $tokens)) as $token) {
            if (isset($found[$token])) {
                $ordered[] = ['token' => $token] + $found[$token];
            }
        }

        return $ordered;
    }

    private function membershipName(Membership $membership): string
    {
        $name = trim((string) ($membership->person?->first_name ?? $membership->first_name ?? '').' '.(string) ($membership->person?->last_name ?? $membership->last_name ?? ''));

        return $name !== '' ? $name : 'Mitglied ohne Namen';
    }

    public function count(string $kind, ?Seminar $seminar = null, string $audience = 'all'): int
    {
        return count($kind === Mailing::KIND_MEMBERS ? $this->members() : $this->team($seminar, $audience));
    }

    /** @return list<User> */
    private function applicants(?Seminar $seminar, ApplicationStatus $status): array
    {
        if ($seminar === null) {
            return [];
        }

        return $seminar->applications()->where('status', $status->value)->with('user.person')->get()->pluck('user')->filter()->values()->all();
    }

    /**
     * @param  list<array<string, string>>  $list
     * @return list<array{email: string, first: string, last: string, fallback: string, group: string}>
     */
    private function unique(array $list): array
    {
        $seen = [];
        $result = [];

        foreach ($list as $entry) {
            $email = mb_strtolower(trim($entry['email']));

            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || isset($seen[$email])) {
                continue;
            }

            $seen[$email] = true;
            $result[] = ['email' => $email, 'first' => trim($entry['first']), 'last' => trim($entry['last']), 'fallback' => $entry['fallback'] ?? 'Mitglied', 'group' => $entry['group'] ?? 'members'];
        }

        return $result;
    }
}

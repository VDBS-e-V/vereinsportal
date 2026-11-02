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
        return Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()))
            ->with('person')
            ->get();
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
     * Empfänger aus mehreren Gruppen, jede Adresse einmal (die erste Gruppe zählt).
     *
     * @param  list<string>  $groups
     * @return list<array{email: string, first: string, last: string, fallback: string, group: string}>
     */
    public function groups(array $groups, bool $mandatory = false): array
    {
        return $this->filter($this->rawGroups($groups), $mandatory)['kept'];
    }

    /**
     * Wie groups(), mit den Zahlen der Ausgeschlossenen: Abgemeldete und Gesperrte.
     *
     * @param  list<string>  $groups
     * @return array{kept: list<array<string, string>>, unsubscribed: int, suppressed: int}
     */
    public function groupsWithStats(array $groups, bool $mandatory = false): array
    {
        return $this->filter($this->rawGroups($groups), $mandatory);
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
    private function rawGroups(array $groups): array
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

            if ($definition['optout'] !== null) {
                $users = $this->preferences->filter($users, $definition['optout']);
            }

            foreach ($users as $user) {
                $list[] = ['email' => (string) $user->email, 'first' => (string) ($user->person?->first_name ?? ''), 'last' => (string) ($user->person?->last_name ?? ''), 'fallback' => $definition['fallback'], 'group' => $key];
            }
        }

        return $this->unique($list);
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

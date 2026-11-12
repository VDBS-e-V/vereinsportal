<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailSegment;
use App\Modules\Mailings\Support\MailGroups;
use App\Modules\Mailings\Support\SegmentFilters;
use Illuminate\Support\Collection;

/**
 * Berechnet Verteiler zum Zeitpunkt der Nutzung (Gruppen, Ausschlüsse, Personen, dann die Filter) und verbindet sie mit den Gruppen eines Schreibens.
 * Es werden nie Adressen gespeichert; Abmeldungen und Sperren gelten von jetzt.
 */
final class SegmentResolver
{
    /** Obergrenze für die Empfänger eines Verteilers. */
    public const MAX_RECIPIENTS = 5000;

    public function __construct(private readonly MailRecipients $recipients) {}

    /**
     * @return array{kept: list<array<string, string>>, unsubscribed: int, suppressed: int, excluded: int}
     */
    public function recipients(MailSegment $segment, bool $mandatory = false): array
    {
        $stats = $this->recipients->groupsWithStats($segment->groups(), $mandatory, $segment->exclude(), $segment->people());
        $kept = $stats['kept'];

        foreach (SegmentFilters::normalize($this->flatFilters($segment)) as $key => $value) {
            if ($key === 'seminar_status') {
                continue;
            }

            $allowed = array_flip(SegmentFilters::emails($key, $value, $segment->filters()['seminar_status'] ?? null));
            $kept = array_values(array_filter($kept, fn (array $entry): bool => isset($allowed[mb_strtolower($entry['email'])])));
        }

        return ['kept' => array_slice($kept, 0, self::MAX_RECIPIENTS)] + $stats;
    }

    /**
     * Gruppen, Ausschlüsse, Personen und Verteiler eines Schreibens zusammen. Jede Adresse einmal (die erste zählt).
     *
     * @param  list<string>  $groups
     * @param  list<string>  $exclude
     * @param  list<string>  $people
     * @param  list<int>  $segmentIds
     * @return array{kept: list<array<string, string>>, unsubscribed: int, suppressed: int, excluded: int}
     */
    public function combined(array $groups, bool $mandatory, array $exclude, array $people, array $segmentIds): array
    {
        $stats = $groups === [] && $people === [] ? ['kept' => [], 'unsubscribed' => 0, 'suppressed' => 0, 'excluded' => 0] : $this->recipients->groupsWithStats($groups, $mandatory, $exclude, $people);

        if ($segmentIds === []) {
            return $stats;
        }

        $seen = array_flip(array_map(fn (array $entry): string => mb_strtolower($entry['email']), $stats['kept']));

        foreach (MailSegment::query()->whereIn('id', $segmentIds)->get() as $segment) {
            $result = $this->recipients($segment, $mandatory);
            $stats['unsubscribed'] += $result['unsubscribed'];
            $stats['suppressed'] += $result['suppressed'];
            $stats['excluded'] += $result['excluded'];

            foreach ($result['kept'] as $entry) {
                $email = mb_strtolower($entry['email']);

                if (! isset($seen[$email])) {
                    $seen[$email] = true;
                    $stats['kept'][] = $entry;
                }
            }
        }

        return $stats;
    }

    /** Verteiler, die diese Person beim Schreiben wählen darf: sichtbar für ihren Bereich und nur mit Gruppen, die sie anschreiben darf. @return \Illuminate\Support\Collection<int, MailSegment> */
    public function usableBy(User $user): Collection
    {
        $areas = array_keys(array_filter(['board' => $user->can('mailings.members'), 'office' => $user->can('mailings.send'), 'coordination' => $user->can('mailings.send_limited')]));
        $allowed = array_keys(MailGroups::allowedFor($user));

        return MailSegment::query()->orderBy('name')->get()->filter(fn (MailSegment $segment): bool => array_intersect((array) $segment->visible_to, $areas) !== []
            && array_diff($segment->groups(), $allowed) === []
            && array_diff($segment->exclude(), $allowed) === []
            && ! $this->hasMembers($segment, $user))->values();
    }

    /** Enthält der Verteiler Mitglieder-Personen, die diese Person nicht wählen darf? */
    private function hasMembers(MailSegment $segment, User $user): bool
    {
        return ! $user->can('mailings.members') && collect($segment->people())->contains(fn (string $token): bool => str_starts_with($token, 'm:'));
    }

    /** @return array<string, mixed> */
    private function flatFilters(MailSegment $segment): array
    {
        $filters = $segment->filters();

        if (isset($filters['member_since']) && is_array($filters['member_since'])) {
            $filters['member_since_from'] = $filters['member_since']['from'] ?? null;
            $filters['member_since_to'] = $filters['member_since']['to'] ?? null;
        }

        if (isset($filters['postal_prefix']) && is_array($filters['postal_prefix'])) {
            $filters['postal_prefix'] = implode(',', $filters['postal_prefix']);
        }

        return $filters;
    }
}

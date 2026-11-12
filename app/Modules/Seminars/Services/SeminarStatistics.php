<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Seminars\Models\SeminarClosure;
use App\Modules\Seminars\Models\SeminarDocument;
use Illuminate\Support\Collection;

/**
 * Seminarstatistik über alle abgeschlossenen Seminare, aus den Schnappschüssen der Abschlüsse (nur Zahlen, keine Personendaten).
 * Wieder geöffnete Abschlüsse zählen nicht. Jeder Filter nimmt mehrere Werte.
 */
final class SeminarStatistics
{
    /**
     * @param  array{years?: list<string>, types?: list<string>, schools?: list<int|string>}  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<string, int|float>}
     */
    public function report(array $filters = []): array
    {
        $years = array_map('strval', (array) ($filters['years'] ?? []));
        $types = array_map('strval', (array) ($filters['types'] ?? []));
        $schools = array_map('intval', (array) ($filters['schools'] ?? []));

        $rows = SeminarClosure::query()->whereNull('reopened_at')->with('seminar')->get()
            ->filter(fn (SeminarClosure $closure): bool => $closure->seminar !== null)
            ->map(fn (SeminarClosure $closure): array => $this->row($closure))
            ->filter(fn (array $row): bool => ($years === [] || in_array($row['year'], $years, true))
                && ($types === [] || in_array($row['type_key'], $types, true))
                && ($schools === [] || in_array($row['partner_id'], $schools, true)))
            ->sortByDesc('sort')->values();

        return ['rows' => $rows->all(), 'totals' => [
            'seminars' => $rows->count(),
            'sessions' => (int) $rows->sum('sessions'),
            'hours' => round((float) $rows->sum('hours'), 1),
            'team_size' => (int) $rows->sum('team_size'),
            'participants' => (int) $rows->sum('participants'),
            'quote_cents' => (int) $rows->sum('quote_cents'),
        ]];
    }

    /** @return array{years: list<string>, schools: array<int, string>} Werte für die Filterfelder */
    public function options(): array
    {
        $rows = SeminarClosure::query()->whereNull('reopened_at')->with('seminar.partner')->get()->filter(fn (SeminarClosure $c): bool => $c->seminar !== null);

        return [
            'years' => $rows->map(fn (SeminarClosure $closure): string => $this->year($closure))->unique()->sortDesc()->values()->all(),
            'schools' => $rows->mapWithKeys(fn (SeminarClosure $closure): array => [(int) $closure->seminar->partner_id => (string) ($closure->seminar->partner?->name ?? '')])->sort()->all(),
        ];
    }

    /** Dokumente, deren Aufbewahrungsfrist in den nächsten Tagen abläuft. @return Collection<int, SeminarDocument> */
    public function expiring(int $days = 90, int $limit = 50): Collection
    {
        return SeminarDocument::query()->whereNotNull('retention_until')->whereBetween('retention_until', [now()->startOfDay(), now()->addDays($days)->endOfDay()])
            ->with('seminar')->orderBy('retention_until')->limit($limit)->get();
    }

    /** @return array<string, mixed> */
    private function row(SeminarClosure $closure): array
    {
        $s = $closure->snapshot;
        $seminar = $closure->seminar;

        return [
            'id' => $seminar->id,
            'number' => (string) ($s['number'] ?? $seminar->number),
            'title' => (string) ($s['title'] ?? $seminar->title),
            'type' => (string) ($s['type'] ?? $seminar->type->label()),
            'type_key' => $seminar->type->value,
            'school' => (string) ($s['school'] ?? ''),
            'partner_id' => (int) $seminar->partner_id,
            'year' => $this->year($closure),
            'first_day' => (string) ($s['first_day'] ?? ''),
            'sessions' => (int) ($s['sessions'] ?? 0),
            'hours' => (float) ($s['hours'] ?? 0),
            'team_size' => (int) ($s['team_size'] ?? 0),
            'participants' => (int) ($s['expected_participants'] ?? 0),
            'quote_cents' => (int) ($s['quote_total_cents'] ?? 0),
            'closed_at' => $closure->closed_at,
            'sort' => $this->year($closure).'-'.str_pad((string) $seminar->id, 8, '0', STR_PAD_LEFT),
        ];
    }

    private function year(SeminarClosure $closure): string
    {
        $day = (string) ($closure->snapshot['first_day'] ?? '');

        return preg_match('/(\d{4})$/', $day, $match) === 1 ? $match[1] : $closure->closed_at->format('Y');
    }
}

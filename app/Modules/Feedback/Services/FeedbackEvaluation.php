<?php

namespace App\Modules\Feedback\Services;

use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Access\Models\User;
use App\Modules\Feedback\Models\FeedbackArchive;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Auswertung der Antworten: je Link, je Seminar und über mehrere Seminare. Unter der Mindestzahl an Antworten werden keine Ergebnisse gezeigt,
 * damit niemand auf Einzelne schließen kann. Freitexte erscheinen nur bei den laufenden Antworten, in zufälliger Reihenfolge, nie im Archiv.
 */
final class FeedbackEvaluation
{
    public function __construct(private readonly FeedbackSubmissions $submissions) {}

    /**
     * Ergebnis eines Links. Bei anonymisierten Links aus dem Archiv, sonst aus den Antworten.
     *
     * @return array{link: FeedbackLink, count: int, min: int, enough: bool, expected: ?int, rate: ?float, archived: bool, overall_avg: ?float, questions: list<array<string, mixed>>}
     */
    public function forLink(FeedbackLink $link): array
    {
        $min = max(1, (int) $link->min_responses);
        $expected = $link->audience === 'students' ? ($link->seminar?->expected_participants ?: null) : null;

        if ($link->anonymized_at !== null) {
            $archive = $link->archive;
            $count = (int) ($archive?->responses_count ?? 0);

            return ['link' => $link, 'count' => $count, 'min' => $min, 'enough' => $archive !== null, 'expected' => $expected, 'rate' => $this->rate($count, $expected), 'archived' => true, 'overall_avg' => $archive?->overall_avg, 'questions' => $archive ? (array) $archive->summary : []];
        }

        $responses = $link->responses()->get();
        $count = $responses->count();
        $enough = $count >= $min;
        $questions = $enough ? $this->summarize($this->submissions->questions($link), $responses, true) : [];

        return ['link' => $link, 'count' => $count, 'min' => $min, 'enough' => $enough, 'expected' => $expected, 'rate' => $this->rate($count, $expected), 'archived' => false, 'overall_avg' => $enough ? $this->overall($questions) : null, 'questions' => $questions];
    }

    /** @return list<array<string, mixed>> */
    public function forSeminar(Seminar $seminar): array
    {
        return $seminar->feedbackLinks()->with('seminar')->orderBy('audience')->orderBy('id')->get()->map(fn (FeedbackLink $link): array => $this->forLink($link))->all();
    }

    /**
     * Vergleich über mehrere Seminare: eine Zeile je Link mit genug Antworten und der Durchschnitt der ersten Skalenfrage („Gesamteindruck“).
     *
     * @param  array{years?: list<string>, types?: list<string>, schools?: list<int|string>, audiences?: list<string>}  $filters
     * @return array{rows: list<array<string, mixed>>, averages: array<string, array{avg: float, links: int, responses: int}>, hidden: int}
     */
    public function overview(array $filters = []): array
    {
        $years = array_map('strval', (array) ($filters['years'] ?? []));
        $types = array_map('strval', (array) ($filters['types'] ?? []));
        $schools = array_map('intval', (array) ($filters['schools'] ?? []));
        $audiences = array_map('strval', (array) ($filters['audiences'] ?? []));

        $rows = [];
        $hidden = 0;

        $links = FeedbackLink::query()->with(['seminar.partner', 'seminar.sessions', 'archive'])->get();

        foreach ($links as $link) {
            $seminar = $link->seminar;

            if ($seminar === null) {
                continue;
            }

            $first = $seminar->sessions->min('starts_at');
            $year = $first ? \Illuminate\Support\Carbon::parse($first)->format('Y') : $link->opens_at->format('Y');
            $type = $seminar->type->value ?? (string) $seminar->type;

            if (($years !== [] && ! in_array($year, $years, true)) || ($types !== [] && ! in_array((string) $type, $types, true)) || ($schools !== [] && ! in_array((int) $seminar->partner_id, $schools, true)) || ($audiences !== [] && ! in_array($link->audience, $audiences, true))) {
                continue;
            }

            $result = $this->forLink($link);

            if (! $result['enough']) {
                $hidden++;

                continue;
            }

            $rows[] = ['id' => $seminar->id, 'number' => (string) $seminar->number, 'title' => $seminar->title, 'school' => $seminar->partner?->name ?? '–', 'year' => $year, 'audience' => $link->audience, 'audience_label' => $link->audienceLabel(), 'count' => $result['count'], 'rate' => $result['rate'], 'avg' => $result['overall_avg'], 'archived' => $result['archived'], 'sort' => (string) $first];
        }

        usort($rows, fn (array $a, array $b): int => strcmp($b['sort'], $a['sort']));

        $averages = [];

        foreach (collect($rows)->whereNotNull('avg')->groupBy('audience') as $audience => $group) {
            $weight = (int) $group->sum('count');
            $averages[$audience] = ['avg' => round($group->sum(fn (array $row): float => $row['avg'] * $row['count']) / max(1, $weight), 2), 'links' => $group->count(), 'responses' => $weight];
        }

        return ['rows' => $rows, 'averages' => $averages, 'hidden' => $hidden];
    }

    /** @return array{years: list<string>, schools: array<int, string>} */
    public function options(): array
    {
        $years = FeedbackLink::query()->get(['opens_at'])->map(fn (FeedbackLink $link): string => $link->opens_at->format('Y'))->unique()->sortDesc()->values()->all();
        $schools = Seminar::query()->whereIn('id', FeedbackLink::query()->select('seminar_id'))->with('partner')->get()->mapWithKeys(fn (Seminar $seminar): array => [(int) $seminar->partner_id => (string) ($seminar->partner?->name ?? '–')])->sort()->all();

        return ['years' => $years, 'schools' => $schools];
    }

    /**
     * Anonymisiert einen Link: bei genug Antworten werden die Zahlen archiviert, in jedem Fall werden Einzelantworten, Freitexte und Namen gelöscht.
     *
     * @return string 'archived' | 'deleted' | 'done' (war schon anonymisiert)
     */
    public function anonymize(FeedbackLink $link, ?User $by = null): string
    {
        if ($link->anonymized_at !== null) {
            return 'done';
        }

        return DB::transaction(function () use ($link, $by): string {
            $result = $this->forLink($link);
            $outcome = 'deleted';

            if ($result['enough']) {
                $summary = array_map(function (array $question): array {
                    unset($question['texts']);

                    return $question;
                }, $result['questions']);

                FeedbackArchive::query()->updateOrCreate(['link_id' => $link->id], ['seminar_id' => $link->seminar_id, 'audience' => $link->audience, 'responses_count' => $result['count'], 'overall_avg' => $result['overall_avg'], 'summary' => $summary, 'archived_at' => now()]);
                $outcome = 'archived';
            }

            $link->responses()->delete();

            // Der geltende Seminarabschluss bekommt die Kennzahlen erst jetzt, nach dem Anonymisieren.
            $closure = $link->seminar?->currentClosure();

            if ($closure !== null && $outcome === 'archived') {
                $snapshot = (array) $closure->snapshot;
                $snapshot['feedback'] = $this->archivedFigures($link->seminar);
                $closure->forceFill(['snapshot' => $snapshot])->save();
            }

            $link->forceFill(['anonymized_at' => now(), 'closed_at' => $link->closed_at ?? now()])->save();
            AuditLog::record(AuditEvent::FeedbackLinkAnonymized, $by, null, ['seminar' => $link->seminar_id, 'gruppe' => $link->audienceLabel(), 'ergebnis' => $outcome]);

            return $outcome;
        });
    }

    /**
     * Zahlen der anonymisierten Rückmeldungen eines Seminars je Gruppe (Antworten, gewichteter Durchschnitt des Gesamteindrucks).
     *
     * @return array<string, array{count: int, avg: ?float}>
     */
    public function archivedFigures(Seminar $seminar): array
    {
        $out = [];

        foreach (FeedbackArchive::query()->where('seminar_id', $seminar->id)->get()->groupBy('audience') as $audience => $rows) {
            $count = (int) $rows->sum('responses_count');
            $weighted = $rows->filter(fn ($row): bool => $row->overall_avg !== null)->sum(fn ($row): float => $row->overall_avg * $row->responses_count);
            $withAvg = (int) $rows->filter(fn ($row): bool => $row->overall_avg !== null)->sum('responses_count');
            $out[(string) $audience] = ['count' => $count, 'avg' => $withAvg > 0 ? round($weighted / $withAvg, 2) : null];
        }

        return $out;
    }

    /** Links, deren Aufbewahrung abgelaufen ist (Ende des Zeitraums, bei beendeten Links das frühere Datum, plus Monate aus der Konfiguration). */
    public function due(): Collection
    {
        $months = max(1, (int) config('portal.feedback.retention_months', 12));

        return FeedbackLink::query()->whereNull('anonymized_at')->get()
            ->filter(function (FeedbackLink $link) use ($months): bool {
                $end = $link->closed_at !== null && $link->closed_at->lessThan($link->closes_at) ? $link->closed_at : $link->closes_at;

                return $end->copy()->addMonths($months)->lessThan(now());
            })->values();
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  Collection<int, \App\Modules\Feedback\Models\FeedbackResponse>  $responses
     * @return list<array<string, mixed>>
     */
    public function summarize(array $questions, Collection $responses, bool $withTexts): array
    {
        $out = [];

        foreach ($questions as $question) {
            $id = $question['id'];
            $type = $question['type'];

            if ($type === 'name') {
                continue;
            }

            $values = $responses->map(fn ($response) => $response->answers[$id] ?? null)->filter(fn ($value) => $value !== null && $value !== '' && $value !== []);
            $row = ['id' => $id, 'type' => $type, 'text' => $question['text'], 'n' => $values->count()];

            switch ($type) {
                case 'scale':
                    $values = $values->filter(fn ($v) => is_numeric($v) && $v >= 1 && $v <= 5)->values();
                    $row['n'] = $values->count();
                    $dist = array_fill(1, 5, 0);
                    foreach ($values as $value) {
                        $dist[(int) $value] = ($dist[(int) $value] ?? 0) + 1;
                    }
                    $row['avg'] = $values->count() > 0 ? round($values->avg(), 2) : null;
                    $row['dist'] = $dist;
                    $row['labels'] = $question['labels'] ?? [];
                    break;
                case 'yesno':
                    $row['counts'] = ['yes' => $values->filter(fn ($v) => $v === 'yes')->count(), 'no' => $values->filter(fn ($v) => $v === 'no')->count()];
                    break;
                case 'choice':
                    $row['counts'] = $this->counts($question['options'] ?? [], $values->all());
                    break;
                case 'multi':
                    $row['counts'] = $this->counts($question['options'] ?? [], $values->flatten()->all());
                    break;
                case 'text':
                    if ($withTexts) {
                        $row['texts'] = $values->shuffle()->values()->all();
                    }
                    break;
            }

            $out[] = $row;
        }

        return $out;
    }

    /** @param  list<array<string, mixed>>  $questions */
    private function overall(array $questions): ?float
    {
        foreach ($questions as $question) {
            if ($question['type'] === 'scale') {
                return $question['avg'];
            }
        }

        return null;
    }

    private function rate(int $count, ?int $expected): ?float
    {
        return $expected ? round($count / $expected * 100, 0) : null;
    }

    /**
     * @param  list<string>  $options
     * @param  list<mixed>  $values
     * @return array<string, int>
     */
    private function counts(array $options, array $values): array
    {
        $counts = array_fill_keys($options, 0);

        foreach ($values as $value) {
            if (array_key_exists((string) $value, $counts)) {
                $counts[(string) $value]++;
            }
        }

        return $counts;
    }
}

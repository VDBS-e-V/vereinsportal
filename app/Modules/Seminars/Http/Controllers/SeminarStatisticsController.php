<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Services\SeminarStatistics;
use App\Support\CsvCell;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Seminarstatistik über die abgeschlossenen Seminare (Berechtigung seminars.view). Filter nehmen mehrere Werte. */
class SeminarStatisticsController extends Controller
{
    public function __construct(private readonly SeminarStatistics $statistics) {}

    public function index(Request $request): View
    {
        $options = $this->statistics->options();
        $filters = $this->filters($request, $options);
        $report = $this->statistics->report($filters);
        $typeOptions = SeminarType::options();

        $tags = collect($filters['years'])->map(fn (string $year): array => ['label' => 'Jahr: '.$year, 'href' => $request->fullUrlWithQuery(['jahr' => array_values(array_diff($filters['years'], [$year])) ?: null])])
            ->merge(collect($filters['types'])->map(fn (string $type): array => ['label' => 'Leistung: '.($typeOptions[$type] ?? $type), 'href' => $request->fullUrlWithQuery(['leistung' => array_values(array_diff($filters['types'], [$type])) ?: null])]))
            ->merge(collect($filters['schools'])->map(fn (int $id): array => ['label' => 'Schule: '.($options['schools'][$id] ?? $id), 'href' => $request->fullUrlWithQuery(['schule' => array_values(array_diff($filters['schools'], [$id])) ?: null])]))
            ->values()->all();

        return view('seminars.statistics', [
            'report' => $report,
            'filters' => $filters,
            'tags' => $tags,
            'yearOptions' => array_combine($options['years'], $options['years']),
            'typeOptions' => $typeOptions,
            'schoolOptions' => $options['schools'],
            'expiring' => $request->user()?->can('seminars.documents') ? $this->statistics->expiring() : collect(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $report = $this->statistics->report($this->filters($request, $this->statistics->options()));

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nummer', 'Seminar', 'Leistung', 'Schule', 'Jahr', 'Erster Tag', 'Termine', 'Stunden', 'Team', 'Teilnehmende (erwartet)', 'Kostenvoranschlag (Euro)', 'Abgeschlossen am'], ';', '"', '\\');

            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map([CsvCell::class, 'safe'], [$row['number'], $row['title'], $row['type'], $row['school'], $row['year'], $row['first_day'], $row['sessions'], number_format($row['hours'], 1, ',', ''), $row['team_size'], $row['participants'], number_format($row['quote_cents'] / 100, 2, ',', ''), $row['closed_at']->format('d.m.Y')]), ';', '"', '\\');
            }

            $t = $report['totals'];
            fputcsv($out, array_map([CsvCell::class, 'safe'], ['Summe', $t['seminars'].' Seminare', '', '', '', '', $t['sessions'], number_format($t['hours'], 1, ',', ''), $t['team_size'], $t['participants'], number_format($t['quote_cents'] / 100, 2, ',', ''), '']), ';', '"', '\\');
            fclose($out);
        }, 'seminarstatistik.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array{years: list<string>, schools: array<int, string>}  $options
     * @return array{years: list<string>, types: list<string>, schools: list<int>}
     */
    private function filters(Request $request, array $options): array
    {
        return [
            'years' => array_values(array_intersect($options['years'], array_map('strval', Arr::wrap($request->query('jahr'))))),
            'types' => array_values(array_intersect(array_keys(SeminarType::options()), array_map('strval', Arr::wrap($request->query('leistung'))))),
            'schools' => array_values(array_intersect(array_keys($options['schools']), array_map('intval', Arr::wrap($request->query('schule'))))),
        ];
    }
}

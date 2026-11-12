<?php

namespace App\Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Feedback\Models\FeedbackForm;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Feedback\Services\FeedbackEvaluation;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use App\Support\CsvCell;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Auswertung der Rückmeldungen (Berechtigung feedback.view, nur Koordination): je Seminar und im Vergleich. Anonymisieren braucht feedback.manage. */
class FeedbackEvaluationController extends Controller
{
    public function __construct(private readonly FeedbackEvaluation $evaluation) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $options = $this->evaluation->options();
        $report = $this->evaluation->overview($filters);
        $typeOptions = SeminarType::options();

        $tags = collect($filters['years'])->map(fn (string $y): array => ['label' => 'Jahr: '.$y, 'href' => $request->fullUrlWithQuery(['jahr' => array_values(array_diff($filters['years'], [$y])) ?: null])])
            ->merge(collect($filters['types'])->map(fn (string $t): array => ['label' => 'Leistung: '.($typeOptions[$t] ?? $t), 'href' => $request->fullUrlWithQuery(['leistung' => array_values(array_diff($filters['types'], [$t])) ?: null])]))
            ->merge(collect($filters['schools'])->map(fn (int $id): array => ['label' => 'Schule: '.($options['schools'][$id] ?? $id), 'href' => $request->fullUrlWithQuery(['schule' => array_values(array_diff($filters['schools'], [$id])) ?: null])]))
            ->merge(collect($filters['audiences'])->map(fn (string $a): array => ['label' => 'Gruppe: '.(FeedbackForm::AUDIENCES[$a] ?? $a), 'href' => $request->fullUrlWithQuery(['gruppe' => array_values(array_diff($filters['audiences'], [$a])) ?: null])]))
            ->values()->all();

        return view('feedback.evaluation', ['report' => $report, 'filters' => $filters, 'tags' => $tags, 'yearOptions' => array_combine($options['years'], $options['years']), 'typeOptions' => $typeOptions, 'schoolOptions' => $options['schools'], 'audienceOptions' => FeedbackForm::AUDIENCES]);
    }

    public function export(Request $request): StreamedResponse
    {
        $report = $this->evaluation->overview($this->filters($request));

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nummer', 'Seminar', 'Schule', 'Jahr', 'Gruppe', 'Antworten', 'Rücklauf (%)', 'Gesamteindruck (Durchschnitt 1 bis 5)'], ';', '"', '\\');

            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map([CsvCell::class, 'safe'], [$row['number'], $row['title'], $row['school'], $row['year'], $row['audience_label'], $row['count'], $row['rate'] ?? '', $row['avg'] !== null ? number_format($row['avg'], 2, ',', '') : '']), ';', '"', '\\');
            }

            fclose($out);
        }, 'feedback-auswertung.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function seminar(Seminar $seminar): View
    {
        return view('feedback.seminar-evaluation', ['seminar' => $seminar, 'results' => $this->evaluation->forSeminar($seminar)]);
    }

    public function anonymize(Request $request, Seminar $seminar): RedirectResponse
    {
        $ids = array_map('intval', (array) $request->input('ids', []));

        if ($ids === []) {
            return back()->withErrors(['feedback' => 'Bitte wählen Sie mindestens einen Link aus.']);
        }

        $count = 0;

        foreach (FeedbackLink::query()->where('seminar_id', $seminar->id)->whereIn('id', $ids)->get() as $link) {
            $this->evaluation->anonymize($link, $request->user());
            $count++;
        }

        return redirect()->route('feedback-eval.seminar', $seminar)->with('status', $count.' Link(s) anonymisiert. Einzelantworten, Freitexte und Namen sind gelöscht; die Zahlen bleiben im Archiv, wenn genug Antworten da waren.');
    }

    /** @return array{years: list<string>, types: list<string>, schools: list<int>, audiences: list<string>} */
    private function filters(Request $request): array
    {
        $options = $this->evaluation->options();
        $types = array_keys(SeminarType::options());

        return [
            'years' => array_values(array_intersect(array_map('strval', (array) $request->query('jahr', [])), $options['years'])),
            'types' => array_values(array_intersect(array_map('strval', (array) $request->query('leistung', [])), $types)),
            'schools' => array_values(array_intersect(array_map('intval', (array) $request->query('schule', [])), array_keys($options['schools']))),
            'audiences' => array_values(array_intersect(array_map('strval', (array) $request->query('gruppe', [])), array_keys(FeedbackForm::AUDIENCES))),
        ];
    }
}

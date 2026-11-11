<?php

namespace App\Http\Controllers;

use App\Support\CsvCell;
use App\Support\YearReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Jahresbericht für den Vorstand (Berechtigung reports.view): Kennzahlen je Kalenderjahr als Seite und als CSV. */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $year = $this->year($request);

        return view('portal.reports', ['year' => $year, 'years' => YearReport::years(), 'rows' => collect(YearReport::rows($year))->groupBy('group')]);
    }

    public function export(Request $request): StreamedResponse
    {
        $year = $this->year($request);

        return response()->streamDownload(function () use ($year): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Jahr', 'Bereich', 'Kennzahl', 'Wert'], ';', '"', '\\');

            foreach (YearReport::rows($year) as $row) {
                fputcsv($out, array_map([CsvCell::class, 'safe'], [$year, $row['group'], $row['label'], $row['value']]), ';', '"', '\\');
            }

            fclose($out);
        }, 'jahresbericht-'.$year.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function year(Request $request): int
    {
        $year = (int) $request->query('jahr', now()->year);

        return in_array($year, YearReport::years(), true) ? $year : (int) now()->year;
    }
}

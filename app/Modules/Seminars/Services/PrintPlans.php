<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Models\SeminarPrintPlan;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Online-Druckplaner der Seminarmappe: je Dokument wird bestimmt, welche Seiten wie oft gedruckt werden, schwarzweiß oder in Farbe, wie viele Seiten auf ein Blatt
 * passen und ob beidseitig gedruckt wird. Der Plan rechnet Seiten und Blätter aus und liefert die Druckliste für Druckerei oder Büro.
 */
final class PrintPlans
{
    public const PER_SHEET = [1, 2, 4];

    /** Dokumente, die in den Plan dürfen: Verträge, Kostenvoranschläge und Rechnungen nur für Personen mit seminars.documents. @return Collection<int, SeminarDocument> */
    public function documents(Seminar $seminar, User $user): Collection
    {
        return $seminar->documents()->where('is_snapshot', false)->orderBy('kind')->orderBy('id')->get()
            ->filter(fn (SeminarDocument $document): bool => $user->can('seminars.documents') || ! in_array($document->kind, SeminarDocument::PROTECTED_KINDS, true))->values();
    }

    public function plan(Seminar $seminar): ?SeminarPrintPlan
    {
        return SeminarPrintPlan::query()->where('seminar_id', $seminar->id)->first();
    }

    /**
     * Prüft die Eingaben, rechnet und speichert den Plan.
     *
     * @param  array<int|string, array<string, mixed>>  $input  je Dokument: use, pages, copies, color, per_sheet, duplex, page_count
     * @return array{rows: list<array<string, mixed>>, totals: array<string, int>}
     *
     * @throws DomainException
     */
    public function save(Seminar $seminar, User $by, array $input): array
    {
        $documents = $this->documents($seminar, $by)->keyBy('id');
        $items = [];

        foreach ($documents as $id => $document) {
            $row = (array) ($input[$id] ?? []);

            if (empty($row['use'])) {
                continue;
            }

            $total = $this->totalPages($document, $row);
            $spec = trim((string) ($row['pages'] ?? ''));
            $this->selected($spec, $total, $document->title ?: $document->original_name);
            $copies = (int) ($row['copies'] ?? 1);
            $perSheet = (int) ($row['per_sheet'] ?? 1);

            if ($copies < 1 || $copies > 500) {
                throw new DomainException('Die Zahl der Exemplare bei „'.($document->title ?: $document->original_name).'“ muss zwischen 1 und 500 liegen.');
            }

            if (! in_array($perSheet, self::PER_SHEET, true)) {
                throw new DomainException('Bitte wählen Sie 1, 2 oder 4 Seiten pro Blatt.');
            }

            $items[] = ['document_id' => (int) $id, 'pages' => $spec, 'copies' => $copies, 'color' => ($row['color'] ?? 'bw') === 'color' ? 'color' : 'bw', 'per_sheet' => $perSheet, 'duplex' => ! empty($row['duplex']), 'page_count' => $total];
        }

        SeminarPrintPlan::query()->updateOrCreate(['seminar_id' => $seminar->id], ['items' => $items, 'updated_by_user_id' => $by->id]);

        return $this->summary($seminar, $items);
    }

    /**
     * Rechnet einen Plan aus.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{rows: list<array<string, mixed>>, totals: array<string, int>}
     */
    public function summary(Seminar $seminar, array $items): array
    {
        $documents = $seminar->documents()->get()->keyBy('id');
        $rows = [];
        $totals = ['pages_bw' => 0, 'pages_color' => 0, 'sheets_bw' => 0, 'sheets_color' => 0, 'sheets' => 0];

        foreach ($items as $item) {
            $document = $documents->get($item['document_id']);

            if ($document === null) {
                continue;
            }

            $selected = $this->selected((string) $item['pages'], (int) $item['page_count'], $document->title ?: $document->original_name);
            $faces = (int) ceil($selected / max(1, (int) $item['per_sheet']));
            $sheetsPerCopy = ! empty($item['duplex']) ? (int) ceil($faces / 2) : $faces;
            $copies = (int) $item['copies'];
            $color = $item['color'] === 'color' ? 'color' : 'bw';

            $rows[] = ['document' => $document, 'pages' => (string) $item['pages'], 'selected' => $selected, 'copies' => $copies, 'color' => $color, 'per_sheet' => (int) $item['per_sheet'], 'duplex' => ! empty($item['duplex']), 'sheets' => $sheetsPerCopy * $copies, 'total_pages' => $selected * $copies];
            $totals['pages_'.$color] += $selected * $copies;
            $totals['sheets_'.$color] += $sheetsPerCopy * $copies;
            $totals['sheets'] += $sheetsPerCopy * $copies;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /** Anzahl der gewählten Seiten aus einer Angabe wie „1-3, 5“ (leer oder „alle“ = alle). @throws DomainException */
    public function selected(string $spec, int $total, string $name = ''): int
    {
        $spec = mb_strtolower(trim($spec));

        if ($spec === '' || $spec === 'alle') {
            return $total;
        }

        $pages = [];

        foreach (preg_split('/\s*[,;]\s*/', $spec) ?: [] as $part) {
            if (! preg_match('/^(\d+)(?:\s*-\s*(\d+))?$/', $part, $match)) {
                throw new DomainException('Die Seitenangabe bei „'.$name.'“ ist nicht lesbar. Schreiben Sie zum Beispiel „1-3, 5“.');
            }

            $from = (int) $match[1];
            $to = isset($match[2]) ? (int) $match[2] : $from;

            if ($from < 1 || $to < $from || $to > $total) {
                throw new DomainException('Die Seitenangabe bei „'.$name.'“ passt nicht zum Dokument (es hat '.$total.' Seiten).');
            }

            foreach (range($from, $to) as $page) {
                $pages[$page] = true;
            }
        }

        return count($pages);
    }

    /** @param  array<string, mixed>  $row  @throws DomainException */
    private function totalPages(SeminarDocument $document, array $row): int
    {
        $total = $document->pages ?? (isset($row['page_count']) && $row['page_count'] !== '' ? (int) $row['page_count'] : null);

        if ($total === null || $total < 1 || $total > 5000) {
            throw new DomainException('Bitte tragen Sie die Seitenzahl von „'.($document->title ?: $document->original_name).'“ ein. Das Portal konnte sie nicht lesen.');
        }

        return $total;
    }
}

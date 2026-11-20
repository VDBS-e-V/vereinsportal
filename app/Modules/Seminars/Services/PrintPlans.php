<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Models\SeminarPrintPlan;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Online-Druckplaner: Je Dokument der Seminarmappe wird festgelegt, welche Seiten wie oft gedruckt werden, in Schwarzweiß oder Farbe,
 * mit 1, 2 oder 4 Seiten pro Blatt, einseitig oder doppelseitig. Daraus entsteht eine einzige PDF für einen Druckauftrag:
 * ein Deckblatt mit den Summen, danach erst alle Schwarzweiß-Teile, dann alle Farb-Teile, Exemplare und Seiten pro Blatt schon eingerechnet.
 * Eine PDF kennt keine Druckfarbe je Seite; darum stehen die Seitenbereiche für Schwarzweiß und Farbe auf dem Deckblatt.
 */
final class PrintPlans
{
    public const PER_SHEET = [1, 2, 4];

    public const MAX_COPIES = 500;

    /** Dokumentarten, die nie in den Druckplaner gehören (Verträge mit persönlichen Daten). */
    private const EXCLUDED = ['school_contract', 'team_contract'];

    /** Wer den Planer nutzen darf: Büro und Planung, die Seminarleitung und das zugesagte Team. */
    public function canUse(User $user, Seminar $seminar): bool
    {
        if ($user->can('seminars.review') || $user->can('seminars.plan') || $seminar->lead_user_id === $user->id) {
            return true;
        }

        return $seminar->applications()->where('user_id', $user->id)->where('outcome', 'confirmed')->exists();
    }

    /** PDF-Dokumente mit lesbarer Seitenzahl, ohne Verträge. @return Collection<int, SeminarDocument> */
    public function printable(Seminar $seminar): Collection
    {
        return $seminar->documents()->whereNotIn('kind', self::EXCLUDED)->whereNotNull('pages')->where('mime', 'application/pdf')->get();
    }

    /** Dokumente, die der Planer nicht verwenden kann (kein PDF oder Seitenzahl nicht lesbar). @return Collection<int, SeminarDocument> */
    public function unusable(Seminar $seminar): Collection
    {
        return $seminar->documents()->whereNotIn('kind', self::EXCLUDED)->where(fn ($query) => $query->whereNull('pages')->orWhere('mime', '!=', 'application/pdf'))->get();
    }

    /**
     * Seitenliste aus „alle“ oder „1-3, 5“. Seiten außerhalb des Dokuments oder falsche Schreibweise geben null.
     *
     * @return list<int>|null
     */
    public static function parsePages(string $input, int $total): ?array
    {
        $input = mb_strtolower(trim($input));

        if ($input === '' || $input === 'alle' || $input === 'all') {
            return range(1, $total);
        }

        $pages = [];

        foreach (explode(',', $input) as $part) {
            $part = trim($part);

            if (preg_match('/^(\d{1,4})(?:\s*-\s*(\d{1,4}))?$/', $part, $match) !== 1) {
                return null;
            }

            $from = (int) $match[1];
            $to = isset($match[2]) ? (int) $match[2] : $from;

            if ($from < 1 || $to < $from || $to > $total) {
                return null;
            }

            foreach (range($from, $to) as $page) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * Prüft die Eingaben des Planers und macht daraus saubere Einträge. Nur ausgewählte Dokumente kommen in den Plan.
     *
     * @param  array<int|string, mixed>  $rows  Formularzeilen je Dokument-ID
     * @return list<array{document_id: int, pages: string, copies: int, color: bool, per_sheet: int, duplex: bool}>
     *
     * @throws DomainException
     */
    public function normalize(Seminar $seminar, array $rows): array
    {
        $documents = $this->printable($seminar)->keyBy('id');
        $items = [];

        foreach ($rows as $id => $row) {
            $row = (array) $row;

            if (empty($row['use'])) {
                continue;
            }

            $document = $documents->get((int) $id);

            if ($document === null) {
                throw new DomainException('Ein ausgewähltes Dokument gibt es nicht oder es lässt sich nicht drucken.');
            }

            $pages = trim((string) ($row['pages'] ?? ''));

            if (self::parsePages($pages, (int) $document->pages) === null) {
                throw new DomainException('Bei „'.$document->original_name.'“ stimmen die Seiten nicht. Das Dokument hat '.$document->pages.' Seiten; schreiben Sie „alle“ oder zum Beispiel „1-3, 5“.');
            }

            $copies = (int) ($row['copies'] ?? 1);
            $perSheet = (int) ($row['per_sheet'] ?? 1);

            if ($copies < 1 || $copies > self::MAX_COPIES) {
                throw new DomainException('Bei „'.$document->original_name.'“ ist die Zahl der Exemplare 1 bis '.self::MAX_COPIES.'.');
            }

            if (! in_array($perSheet, self::PER_SHEET, true)) {
                throw new DomainException('Seiten pro Blatt: 1, 2 oder 4.');
            }

            $items[] = ['document_id' => (int) $id, 'pages' => $pages === '' ? 'alle' : $pages, 'copies' => $copies, 'color' => ($row['color'] ?? 'bw') === 'color', 'per_sheet' => $perSheet, 'duplex' => ! empty($row['duplex'])];
        }

        if ($items === []) {
            throw new DomainException('Bitte wählen Sie mindestens ein Dokument aus.');
        }

        return $items;
    }

    public function save(Seminar $seminar, array $items, User $by): SeminarPrintPlan
    {
        return SeminarPrintPlan::query()->updateOrCreate(['seminar_id' => $seminar->id], ['items' => $items, 'updated_by_user_id' => $by->id]);
    }

    /**
     * Blätter und Seiten je Eintrag: ausgewählte Seiten, Blätter (Seiten pro Blatt eingerechnet), Papier (doppelseitig halbiert) und alles mal Exemplare.
     *
     * @param  array{pages: string, copies: int, per_sheet: int, duplex: bool}  $item
     * @return array{pages: int, sheets: int, paper: int, total_paper: int, total_sheets: int}
     */
    public static function counts(array $item, int $documentPages): array
    {
        $pages = count(self::parsePages($item['pages'], $documentPages) ?? []);
        $sheets = (int) ceil($pages / $item['per_sheet']);
        $paper = $item['duplex'] ? (int) ceil($sheets / 2) : $sheets;

        return ['pages' => $pages, 'sheets' => $sheets, 'paper' => $paper, 'total_paper' => $paper * $item['copies'], 'total_sheets' => $sheets * $item['copies']];
    }

    /**
     * Summen des Plans: Papierblätter in Schwarzweiß und in Farbe sowie die Zahl der Seiten im Druckauftrag.
     *
     * @param  list<array{document_id: int, pages: string, copies: int, color: bool, per_sheet: int, duplex: bool}>  $items
     * @return array{bw: int, color: int, total: int}
     */
    public function totals(Seminar $seminar, array $items): array
    {
        $documents = $this->printable($seminar)->keyBy('id');
        $bw = 0;
        $color = 0;

        foreach ($items as $item) {
            $document = $documents->get($item['document_id']);

            if ($document === null) {
                continue;
            }

            $counts = self::counts($item, (int) $document->pages);
            $item['color'] ? $color += $counts['total_paper'] : $bw += $counts['total_paper'];
        }

        return ['bw' => $bw, 'color' => $color, 'total' => $bw + $color];
    }

    /**
     * Erzeugt die Druck-PDF: Deckblatt, dann Schwarzweiß-, dann Farb-Teile.
     *
     * @param  list<array{document_id: int, pages: string, copies: int, color: bool, per_sheet: int, duplex: bool}>  $items
     *
     * @throws DomainException
     */
    public function render(Seminar $seminar, array $items): string
    {
        $documents = $this->printable($seminar)->keyBy('id');
        $ordered = array_merge(array_values(array_filter($items, fn (array $item): bool => ! $item['color'])), array_values(array_filter($items, fn (array $item): bool => $item['color'])));

        try {
            $body = new Fpdi;
            $body->SetAutoPageBreak(false);
            $sections = [];

            foreach ($ordered as $item) {
                $document = $documents->get($item['document_id']);

                if ($document === null || ! Storage::disk(SeminarDocument::DISK)->exists($document->path)) {
                    throw new DomainException('Ein Dokument des Druckplans gibt es nicht mehr. Bitte speichern Sie den Plan neu.');
                }

                $source = Storage::disk(SeminarDocument::DISK)->path($document->path);
                $body->setSourceFile($source);
                $selected = self::parsePages($item['pages'], (int) $document->pages) ?? [];
                $start = $body->PageNo() + 1;

                for ($copy = 0; $copy < $item['copies']; $copy++) {
                    $sheets = 0;

                    foreach (array_chunk($selected, $item['per_sheet']) as $chunk) {
                        $this->sheet($body, $chunk, $item['per_sheet']);
                        $sheets++;
                    }

                    // doppelseitig: jedes Exemplar beginnt auf einer Vorderseite
                    if ($item['duplex'] && $sheets % 2 === 1) {
                        $body->AddPage($item['per_sheet'] === 2 ? 'L' : 'P', 'A4');
                    }
                }

                $sections[] = ['document' => $document, 'item' => $item, 'from' => $start + 1, 'to' => $body->PageNo() + 1];
            }

            $content = (string) $body->Output('S');

            $final = new Fpdi;
            $final->SetAutoPageBreak(false);
            $this->cover($final, $seminar, $sections, $body->PageNo());
            $count = $final->setSourceFile(StreamReader::createByString($content));

            for ($page = 1; $page <= $count; $page++) {
                $template = $final->importPage($page);
                $size = $final->getTemplateSize($template);
                $final->AddPage(is_array($size) ? (string) $size['orientation'] : 'P', 'A4');
                $final->useTemplate($template);
            }

            return (string) $final->Output('S');
        } catch (DomainException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            throw new DomainException('Die PDF konnte nicht erzeugt werden. Vermutlich lässt sich eines der Dokumente nicht verarbeiten (zum Beispiel PDF 1.5 mit komprimierter Struktur): speichern Sie es als PDF 1.4 neu und laden Sie es wieder hoch.');
        }
    }

    /** Ein Blatt mit 1, 2 oder 4 Seiten (Seiten verkleinert und mittig in ihrer Zelle). @param  list<int>  $pages */
    private function sheet(Fpdi $pdf, array $pages, int $perSheet): void
    {
        if ($perSheet === 2) {
            $pdf->AddPage('L', 'A4');
            $cells = [[0.0, 0.0, 148.5, 210.0], [148.5, 0.0, 148.5, 210.0]];
        } elseif ($perSheet === 4) {
            $pdf->AddPage('P', 'A4');
            $cells = [[0.0, 0.0, 105.0, 148.5], [105.0, 0.0, 105.0, 148.5], [0.0, 148.5, 105.0, 148.5], [105.0, 148.5, 105.0, 148.5]];
        } else {
            $pdf->AddPage('P', 'A4');
            $cells = [[0.0, 0.0, 210.0, 297.0]];
        }

        foreach ($pages as $index => $page) {
            [$x, $y, $width, $height] = $cells[$index];
            $margin = $perSheet === 1 ? 0.0 : 4.0;
            $template = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($template);

            if (! is_array($size)) {
                continue;
            }

            $scale = min(($width - 2 * $margin) / (float) $size['width'], ($height - 2 * $margin) / (float) $size['height']);
            $w = (float) $size['width'] * $scale;
            $h = (float) $size['height'] * $scale;
            $pdf->useTemplate($template, $x + ($width - $w) / 2, $y + ($height - $h) / 2, $w, $h);
        }
    }

    /**
     * Deckblatt mit den Summen und den Seitenbereichen für Schwarzweiß und Farbe.
     *
     * @param  list<array{document: SeminarDocument, item: array{document_id: int, pages: string, copies: int, color: bool, per_sheet: int, duplex: bool}, from: int, to: int}>  $sections
     */
    private function cover(Fpdi $pdf, Seminar $seminar, array $sections, int $contentPages): void
    {
        $text = fn (string $value): string => (string) iconv('UTF-8', 'windows-1252//TRANSLIT', $value);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, $text('Druckauftrag: '.$seminar->title), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, $text($seminar->number.' · '.$seminar->partner->name.' · erstellt am '.now()->format('d.m.Y')), 0, 1);
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 7, $text('Dieses Deckblatt nicht drucken. Gedruckt werden die Seiten 2 bis '.($contentPages + 1).'.'), 0, 1);
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', '', 10);

        $paperBw = 0;
        $paperColor = 0;

        foreach ($sections as $section) {
            $item = $section['item'];
            $counts = self::counts($item, (int) $section['document']->pages);
            $item['color'] ? $paperColor += $counts['total_paper'] : $paperBw += $counts['total_paper'];

            $line = ($item['color'] ? 'FARBE' : 'Schwarzweiß').' · Seiten '.$section['from'].'-'.$section['to'].' · '.$section['document']->original_name.' · Seiten '.$item['pages'].', '.$item['copies'].' Exemplar(e), '.$item['per_sheet'].' Seite(n) pro Blatt, '.($item['duplex'] ? 'doppelseitig' : 'einseitig').' · '.$counts['total_paper'].' Blatt Papier';
            $pdf->MultiCell(0, 5.5, $text($line), 0, 'L');
            $pdf->Ln(1.5);
        }

        $pdf->Ln(3);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 7, $text('Papier insgesamt: '.($paperBw + $paperColor).' Blatt (Schwarzweiß '.$paperBw.', Farbe '.$paperColor.')'), 0, 1);
    }
}

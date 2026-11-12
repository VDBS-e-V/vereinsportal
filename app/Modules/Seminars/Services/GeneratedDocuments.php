<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use DomainException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;
use Throwable;

/**
 * Vom Portal erzeugte Dokumente zu einem Seminar: Terminübersicht und Teamübersicht als HTML-Druckansicht, auf Wunsch als PDF.
 * Das PDF braucht das Paket dompdf/dompdf (reines PHP). Fehlt es oder scheitert die Erzeugung, bleibt die HTML-Datei; der PDF-Knopf erscheint dann nicht.
 * Erzeugte Dokumente sind Schnappschüsse: sie werden nicht verändert und nach dem Abschluss geschützt.
 */
final class GeneratedDocuments
{
    public function __construct(private readonly SeminarDocuments $documents, private readonly TeamInfo $team) {}

    /** @return array<string, string> Typ => Bezeichnung */
    public static function types(): array
    {
        return ['schedule' => 'Terminübersicht', 'team' => 'Teamübersicht'];
    }

    public static function pdfAvailable(): bool
    {
        return class_exists(Dompdf::class);
    }

    public function render(Seminar $seminar, string $type): string
    {
        $seminar->loadMissing(['partner', 'sessions']);

        return match ($type) {
            'schedule' => view('seminars.generated.schedule', ['seminar' => $seminar])->render(),
            'team' => view('seminars.generated.team', ['seminar' => $seminar, 'team' => $this->team->overview($seminar)])->render(),
            default => throw new DomainException('Dieses Dokument gibt es nicht.'),
        };
    }

    /**
     * Erzeugt das Dokument und legt es ab.
     *
     * @throws DomainException
     */
    public function generate(Seminar $seminar, string $type, string $format, User $by): SeminarDocument
    {
        $label = self::types()[$type] ?? throw new DomainException('Dieses Dokument gibt es nicht.');
        $html = $this->render($seminar, $type);
        $base = Str::slug($label.'-'.($seminar->number ?: $seminar->id));

        if ($format === 'pdf') {
            if (! self::pdfAvailable()) {
                throw new DomainException('Die PDF-Erzeugung ist auf diesem Server nicht eingerichtet. Nutzen Sie die HTML-Datei und drucken Sie sie als PDF.');
            }

            try {
                $options = new Options;
                $options->set('isRemoteEnabled', false);
                $options->set('defaultFont', 'DejaVu Sans');
                $pdf = new Dompdf($options);
                $pdf->loadHtml($html, 'UTF-8');
                $pdf->setPaper('A4');
                $pdf->render();

                return $this->documents->addContent($seminar, (string) $pdf->output(), $base.'.pdf', 'application/pdf', 'other', $label.' (PDF)', $by, true);
            } catch (Throwable $exception) {
                report($exception);

                throw new DomainException('Das PDF konnte nicht erzeugt werden. Nutzen Sie die HTML-Datei und drucken Sie sie als PDF.');
            }
        }

        return $this->documents->addContent($seminar, $html, $base.'.html', 'text/html', 'other', $label, $by, true);
    }
}

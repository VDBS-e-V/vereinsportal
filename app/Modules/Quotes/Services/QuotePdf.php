<?php

namespace App\Modules\Quotes\Services;

use App\Modules\Quotes\Models\SeminarQuote;
use Dompdf\Dompdf;
use Dompdf\Options;

/** Erzeugt das PDF eines Kostenvoranschlags aus der Ansicht quotes.pdf. */
final class QuotePdf
{
    public function render(SeminarQuote $quote): string
    {
        $quote->loadMissing(['lines', 'seminar.partner']);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('quotes.pdf', ['quote' => $quote, 'seminar' => $quote->seminar])->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    public function filename(SeminarQuote $quote): string
    {
        return 'Kostenvoranschlag-'.$quote->seminar->number.'-V'.$quote->version.'.pdf';
    }
}

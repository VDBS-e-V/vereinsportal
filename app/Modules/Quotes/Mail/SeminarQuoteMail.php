<?php

namespace App\Modules\Quotes\Mail;

use App\Modules\Quotes\Models\SeminarQuote;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Kostenvoranschlag an die Schule (Pflicht-Mail, nicht abschaltbar). Das PDF hängt an, die Antwort erfolgt im Portal. */
final class SeminarQuoteMail extends Mailable
{
    public function __construct(public readonly Seminar $seminar, public readonly SeminarQuote $quote, private readonly string $pdf, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kostenvoranschlag '.$this->seminar->number.': '.$this->seminar->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.quotes.sent');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->pdf, 'Kostenvoranschlag-'.$this->seminar->number.'-V'.$this->quote->version.'.pdf')->withMime('application/pdf')];
    }
}

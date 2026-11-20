<?php

namespace App\Modules\Quotes\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;
use App\Modules\Quotes\Models\SeminarQuote;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Mail\Mailables\Attachment;

/** Kostenvoranschlag an die Schule (Pflicht-Mail, nicht abschaltbar). Das PDF hängt an, die Antwort erfolgt im Portal. */
final class SeminarQuoteMail extends TemplatedMail
{
    public function __construct(public readonly Seminar $seminar, public readonly SeminarQuote $quote, private readonly string $pdf, public readonly string $url) {}

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->pdf, 'Kostenvoranschlag-'.$this->seminar->number.'-V'.$this->quote->version.'.pdf')->withMime('application/pdf')];
    }

    protected function systemKey(): string
    {
        return 'quotes.sent';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->seminar->contact_name), 'nummer' => (string) $this->seminar->number, 'version' => (string) $this->quote->version, 'titel' => (string) $this->seminar->title, 'url' => $this->url];
    }
}

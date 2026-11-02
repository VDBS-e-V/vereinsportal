<?php

namespace App\Modules\Mailings\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Ein Schreiben des Vorstands oder der Koordination. Der Text ist HTML aus dem Editor und wird beim Ausgeben bereinigt (rich_text). */
final class MailingMail extends Mailable
{
    public function __construct(public readonly string $subjectLine, public readonly string $bodyHtml, public readonly string $kind) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.mailings.message');
    }
}

<?php

namespace App\Modules\Mailings\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/** Ein Schreiben des Vorstands oder der Koordination. Der Text ist HTML aus dem Editor und wird beim Ausgeben bereinigt (rich_text). */
final class MailingMail extends Mailable
{
    public function __construct(public readonly string $subjectLine, public readonly string $bodyHtml, public readonly string $kind, public readonly ?string $unsubscribeUrl = null, public readonly bool $mandatory = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    /** Abbestellbare Mails tragen List-Unsubscribe und One-Click (RFC 2369 und RFC 8058). */
    public function headers(): Headers
    {
        return new Headers(text: $this->unsubscribeUrl !== null ? ['List-Unsubscribe' => '<'.$this->unsubscribeUrl.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'] : []);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.mailings.message');
    }
}

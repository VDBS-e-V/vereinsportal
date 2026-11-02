<?php

namespace App\Modules\Mailings\Mail;

use App\Modules\Mailings\Models\Mailing;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Hinweis an den Vorstand: Ein Schreiben liegt zur Freigabe vor (Betreff, Zahl der Empfänger, Link, kein Text). */
final class MailingApprovalMail extends Mailable
{
    public function __construct(public readonly Mailing $mailing, public readonly string $author, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Schreiben zur Freigabe: '.$this->mailing->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.mailings.approval');
    }
}

<?php

namespace App\Modules\Forms\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Benachrichtigung zu einem Antrag. Enthält nur Nummer, Art und einen kurzen Text – keine Beträge und keine Belege. */
final class FormStatusMail extends Mailable
{
    public function __construct(public readonly string $number, public readonly string $typeLabel, public readonly string $mailSubject, public readonly string $text) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject.' ('.$this->number.')');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.forms.status');
    }
}

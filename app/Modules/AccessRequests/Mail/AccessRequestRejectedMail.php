<?php

namespace App\Modules\AccessRequests\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Antwort auf einen abgelehnten Antrag, optional mit Begründung. */
final class AccessRequestRejectedMail extends Mailable
{
    public function __construct(public readonly string $name, public readonly ?string $note, public readonly string $contactUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Antrag auf Zugang zum VDBS-Portal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access-requests.rejected');
    }
}

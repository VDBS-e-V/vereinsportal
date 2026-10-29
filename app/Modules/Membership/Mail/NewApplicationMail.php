<?php

namespace App\Modules\Membership\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Hinweis an den Vorstand: Ein Online-Antrag wartet auf Entscheidung. Enthält bewusst keine personenbezogenen Daten, nur den Link. */
final class NewApplicationMail extends Mailable
{
    public function __construct(public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Neuer Mitgliedschaftsantrag im VDBS-Portal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.membership.new-application');
    }
}

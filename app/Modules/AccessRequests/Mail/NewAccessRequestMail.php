<?php

namespace App\Modules\AccessRequests\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Hinweis an die Geschäftsstelle: Ein Zugangsantrag wartet. Enthält keine personenbezogenen Daten, nur den Link. */
final class NewAccessRequestMail extends Mailable
{
    public function __construct(public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Neuer Antrag auf Zugang im VDBS-Portal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access-requests.new');
    }
}

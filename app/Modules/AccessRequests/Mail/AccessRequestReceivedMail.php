<?php

namespace App\Modules\AccessRequests\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Eingangsbestätigung an die Antragstellenden. */
final class AccessRequestReceivedMail extends Mailable
{
    public function __construct(public readonly string $name, public readonly int $reference) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Antrag auf Zugang zum VDBS-Portal ist eingegangen');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access-requests.received');
    }
}

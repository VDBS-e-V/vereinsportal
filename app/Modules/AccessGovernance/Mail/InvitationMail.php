<?php

namespace App\Modules\AccessGovernance\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Einladung ins Portal mit Link zum Festlegen des Passworts. */
final class InvitationMail extends Mailable
{
    public function __construct(
        public readonly string $url,
        public readonly string $name,
        public readonly int $validDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Einladung ins VDBS-Portal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access.invitation');
    }
}

<?php

namespace App\Modules\Access\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** E-Mail mit dem Sicherheitscode für die Zwei-Faktor-Anmeldung per E-Mail. */
final class TwoFactorCodeMail extends Mailable
{
    public function __construct(
        public readonly string $code,
        public readonly int $validMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Sicherheitscode für das VDBS-Portal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access.two-factor-code');
    }
}

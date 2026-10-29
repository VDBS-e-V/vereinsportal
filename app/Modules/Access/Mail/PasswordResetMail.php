<?php

namespace App\Modules\Access\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** E-Mail mit dem Link zum Zurücksetzen des Passworts. */
final class PasswordResetMail extends Mailable
{
    public function __construct(
        public readonly string $url,
        public readonly int $validMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Passwort für das VDBS-Portal zurücksetzen');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.access.password-reset');
    }
}

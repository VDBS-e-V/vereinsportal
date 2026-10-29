<?php

namespace App\Modules\Membership\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** E-Mail-Bestätigung nach dem Online-Antrag: Mit dem Link wird das Konto aktiv. */
final class VerifyApplicantEmailMail extends Mailable
{
    public function __construct(public readonly string $url, public readonly string $name, public readonly int $validHours) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bitte bestätigen Sie Ihre E-Mail-Adresse');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.membership.verify');
    }
}

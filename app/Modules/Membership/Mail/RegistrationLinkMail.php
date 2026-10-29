<?php

namespace App\Modules\Membership\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Registrierungslink des Vorstands an ein bestehendes Mitglied: Selbstregistrierung für ein Online-Konto. */
final class RegistrationLinkMail extends Mailable
{
    public function __construct(public readonly string $url, public readonly string $name, public readonly int $validDays) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Zugang zum VDBS-Portal');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.membership.registration-link');
    }
}

<?php

namespace App\Modules\Membership\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Neutrale Antwort, wenn ein Online-Antrag nicht automatisch angelegt werden kann (E-Mail oder Person gibt es schon).
 * Der Antragsteller erfährt auf der Webseite nichts darüber; nur wer die Adresse kontrolliert, liest diese Nachricht.
 */
final class ApplicationNoticeMail extends Mailable
{
    public function __construct(
        public readonly bool $hasAccount,
        public readonly string $loginUrl,
        public readonly string $resetUrl,
        public readonly string $contactUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Antrag auf Mitgliedschaft im VDBS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.membership.application-notice');
    }
}

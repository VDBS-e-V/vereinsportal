<?php

namespace App\Modules\Onboarding\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Hinweis an neue Teamende: Die Einarbeitung beginnt, mit Link zur Seite im Portal. Enthält keine persönlichen Angaben. */
final class TeamOnboardingStartedMail extends Mailable
{
    public function __construct(public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihre Einarbeitung als Teamende im VDBS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.onboarding.team-started');
    }
}

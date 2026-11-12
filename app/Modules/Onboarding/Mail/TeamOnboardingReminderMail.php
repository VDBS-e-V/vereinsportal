<?php

namespace App\Modules\Onboarding\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Erinnerung an Teamende mit offener Einarbeitung. Nennt nur die Zahl der offenen Schritte, nie welche (Gesundheits- und Führungszeugnisdaten bleiben außen vor). */
final class TeamOnboardingReminderMail extends Mailable
{
    public function __construct(public readonly string $url, public readonly int $open, public readonly int $total) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Erinnerung: Ihre Einarbeitung als Teamende im VDBS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.onboarding.team-reminder');
    }
}

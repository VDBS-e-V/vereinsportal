<?php

namespace App\Modules\Onboarding\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Erinnerung an Teamende mit offener Einarbeitung. Nennt nur die Zahl der offenen Schritte, nie welche (Gesundheits- und Führungszeugnisdaten bleiben außen vor). */
final class TeamOnboardingReminderMail extends TemplatedMail
{
    public function __construct(public readonly string $url, public readonly int $open, public readonly int $total) {}

    protected function systemKey(): string
    {
        return 'onboarding.team_reminder';
    }

    protected function values(): array
    {
        return ['url' => $this->url, 'offen' => (string) $this->open, 'gesamt' => (string) $this->total];
    }
}

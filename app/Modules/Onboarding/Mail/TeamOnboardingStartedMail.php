<?php

namespace App\Modules\Onboarding\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Hinweis an neue Teamende: Die Einarbeitung beginnt, mit Link zur Seite im Portal. Enthält keine persönlichen Angaben. */
final class TeamOnboardingStartedMail extends TemplatedMail
{
    public function __construct(public readonly string $url) {}

    protected function systemKey(): string
    {
        return 'onboarding.team_started';
    }

    protected function values(): array
    {
        return ['url' => $this->url];
    }
}

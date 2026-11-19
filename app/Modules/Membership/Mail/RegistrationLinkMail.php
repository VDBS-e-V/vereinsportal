<?php

namespace App\Modules\Membership\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Registrierungslink des Vorstands an ein bestehendes Mitglied: Selbstregistrierung für ein Online-Konto. */
final class RegistrationLinkMail extends TemplatedMail
{
    public function __construct(public readonly string $url, public readonly string $name, public readonly int $validDays) {}

    protected function systemKey(): string
    {
        return 'membership.registration_link';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->name), 'url' => $this->url, 'tage' => (string) $this->validDays];
    }
}

<?php

namespace App\Modules\AccessGovernance\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Einladung ins Portal mit Link zum Festlegen des Passworts. */
final class InvitationMail extends TemplatedMail
{
    public function __construct(
        public readonly string $url,
        public readonly string $name,
        public readonly int $validDays,
    ) {}

    protected function systemKey(): string
    {
        return 'access.invitation';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->name), 'url' => $this->url, 'tage' => (string) $this->validDays];
    }
}

<?php

namespace App\Modules\Membership\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** E-Mail-Bestätigung nach dem Online-Antrag: Mit dem Link wird das Konto aktiv. */
final class VerifyApplicantEmailMail extends TemplatedMail
{
    public function __construct(public readonly string $url, public readonly string $name, public readonly int $validHours) {}

    protected function systemKey(): string
    {
        return 'membership.verify';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->name), 'url' => $this->url, 'stunden' => (string) $this->validHours];
    }
}

<?php

namespace App\Modules\Access\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** E-Mail mit dem Sicherheitscode für die Zwei-Faktor-Anmeldung per E-Mail. */
final class TwoFactorCodeMail extends TemplatedMail
{
    public function __construct(
        public readonly string $code,
        public readonly int $validMinutes,
    ) {}

    protected function systemKey(): string
    {
        return 'access.two_factor_code';
    }

    protected function values(): array
    {
        return ['code' => $this->code, 'minuten' => (string) $this->validMinutes];
    }
}

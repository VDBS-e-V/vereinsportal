<?php

namespace App\Modules\Access\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** E-Mail mit dem Link zum Zurücksetzen des Passworts. */
final class PasswordResetMail extends TemplatedMail
{
    public function __construct(
        public readonly string $url,
        public readonly int $validMinutes,
    ) {}

    protected function systemKey(): string
    {
        return 'access.password_reset';
    }

    protected function values(): array
    {
        return ['url' => $this->url, 'minuten' => (string) $this->validMinutes];
    }
}

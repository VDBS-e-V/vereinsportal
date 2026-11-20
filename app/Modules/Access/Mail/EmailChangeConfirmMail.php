<?php

namespace App\Modules\Access\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** An die NEUE Adresse: Link zur Bestätigung der E-Mail-Änderung. */
final class EmailChangeConfirmMail extends TemplatedMail
{
    public function __construct(
        public readonly string $url,
        public readonly string $newEmail,
        public readonly int $validHours,
    ) {}

    protected function systemKey(): string
    {
        return 'access.email_change_confirm';
    }

    protected function values(): array
    {
        return ['url' => $this->url, 'neue_adresse' => $this->newEmail, 'stunden' => (string) $this->validHours];
    }
}

<?php

namespace App\Modules\Access\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Bestätigung der beantragten Kontolöschung mit Link zum Abbrechen innerhalb der Wartefrist. */
final class AccountDeletionRequestedMail extends TemplatedMail
{
    public function __construct(public readonly string $cancelUrl, public readonly int $graceDays, public readonly string $until) {}

    protected function systemKey(): string
    {
        return 'access.account_deletion_requested';
    }

    protected function values(): array
    {
        return ['url' => $this->cancelUrl, 'tage' => (string) $this->graceDays, 'bis' => $this->until];
    }
}

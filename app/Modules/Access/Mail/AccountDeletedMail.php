<?php

namespace App\Modules\Access\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Letzte Nachricht an die alte Adresse: Das Konto wurde gelöscht (anonymisiert). */
final class AccountDeletedMail extends TemplatedMail
{
    protected function systemKey(): string
    {
        return 'access.account_deleted';
    }

    protected function values(): array
    {
        return [];
    }
}

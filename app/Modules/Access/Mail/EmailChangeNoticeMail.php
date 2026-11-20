<?php

namespace App\Modules\Access\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** An die ALTE Adresse: Hinweis, dass eine Änderung beantragt wurde (Schutz vor Missbrauch). */
final class EmailChangeNoticeMail extends TemplatedMail
{
    public function __construct(
        public readonly string $newEmail,
    ) {}

    protected function systemKey(): string
    {
        return 'access.email_change_notice';
    }

    protected function values(): array
    {
        return ['neue_adresse' => $this->newEmail];
    }
}

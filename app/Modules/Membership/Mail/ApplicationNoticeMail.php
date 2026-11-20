<?php

namespace App\Modules\Membership\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/**
 * Neutrale Antwort, wenn ein Online-Antrag nicht automatisch angelegt werden kann (E-Mail oder Person gibt es schon).
 * Der Antragsteller erfährt auf der Webseite nichts darüber; nur wer die Adresse kontrolliert, liest diese Nachricht.
 */
final class ApplicationNoticeMail extends TemplatedMail
{
    public function __construct(
        public readonly bool $hasAccount,
        public readonly string $loginUrl,
        public readonly string $resetUrl,
        public readonly string $contactUrl,
    ) {}

    protected function systemKey(): string
    {
        return 'membership.application_notice';
    }

    protected function values(): array
    {
        return ['konto' => $this->hasAccount ? '1' : '', 'login_url' => $this->loginUrl, 'reset_url' => $this->resetUrl, 'kontakt_url' => $this->contactUrl];
    }
}

<?php

namespace App\Modules\AccessRequests\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Neutrale Antwort, wenn es zur Adresse schon ein Konto oder einen offenen Antrag gibt. Verrät nichts, was Fremde nicht wissen dürfen. */
final class AccessRequestNoticeMail extends TemplatedMail
{
    public function __construct(public readonly string $loginUrl, public readonly string $resetUrl, public readonly string $contactUrl) {}

    protected function systemKey(): string
    {
        return 'access_requests.notice';
    }

    protected function values(): array
    {
        return ['login_url' => $this->loginUrl, 'reset_url' => $this->resetUrl, 'kontakt_url' => $this->contactUrl];
    }
}

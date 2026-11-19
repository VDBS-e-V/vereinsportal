<?php

namespace App\Modules\AccessRequests\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Antwort auf einen abgelehnten Antrag, optional mit Begründung. */
final class AccessRequestRejectedMail extends TemplatedMail
{
    public function __construct(public readonly string $name, public readonly ?string $note, public readonly string $contactUrl) {}

    protected function systemKey(): string
    {
        return 'access_requests.rejected';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->name), 'begruendung' => (string) $this->note, 'kontakt_url' => $this->contactUrl];
    }
}

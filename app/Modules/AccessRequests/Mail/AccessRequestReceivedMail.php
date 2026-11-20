<?php

namespace App\Modules\AccessRequests\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Eingangsbestätigung an die Antragstellenden. */
final class AccessRequestReceivedMail extends TemplatedMail
{
    public function __construct(public readonly string $name, public readonly int $reference) {}

    protected function systemKey(): string
    {
        return 'access_requests.received';
    }

    protected function values(): array
    {
        return ['anrede' => self::greeting($this->name), 'nummer' => (string) $this->reference];
    }
}

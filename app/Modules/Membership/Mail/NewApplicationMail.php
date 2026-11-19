<?php

namespace App\Modules\Membership\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Hinweis an den Vorstand: Ein Online-Antrag wartet auf Entscheidung. Enthält bewusst keine personenbezogenen Daten, nur den Link. */
final class NewApplicationMail extends TemplatedMail
{
    public function __construct(public readonly string $url) {}

    protected function systemKey(): string
    {
        return 'membership.new_application';
    }

    protected function values(): array
    {
        return ['url' => $this->url];
    }
}

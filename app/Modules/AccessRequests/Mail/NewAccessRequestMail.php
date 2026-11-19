<?php

namespace App\Modules\AccessRequests\Mail;

use App\Modules\MailBuilder\Mail\TemplatedMail;

/** Hinweis an die Geschäftsstelle: Ein Zugangsantrag wartet. Enthält keine personenbezogenen Daten, nur den Link. */
final class NewAccessRequestMail extends TemplatedMail
{
    public function __construct(public readonly string $url) {}

    protected function systemKey(): string
    {
        return 'access_requests.new';
    }

    protected function values(): array
    {
        return ['url' => $this->url];
    }
}

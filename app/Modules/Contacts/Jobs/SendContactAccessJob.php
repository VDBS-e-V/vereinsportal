<?php

namespace App\Modules\Contacts\Jobs;

use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Contacts\Services\ContactAccessSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Sendet einen persönlichen Zugang. Nimmt nur die Nummer des Kontakts mit, nie Link oder Code. */
final class SendContactAccessJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $contactId) {}

    public function handle(ContactAccessSender $sender): void
    {
        $contact = TeamContact::query()->find($this->contactId);

        if ($contact !== null) {
            $sender->deliver($contact);
        }
    }
}

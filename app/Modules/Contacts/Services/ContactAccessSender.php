<?php

namespace App\Modules\Contacts\Services;

use App\Modules\Contacts\Jobs\SendContactAccessJob;
use App\Modules\Contacts\Mail\ContactAccessMail;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Services\MailUnsubscribes;
use Illuminate\Support\Facades\Mail;

/**
 * Verschickt persönliche Zugänge. Link und Code entstehen erst im Moment des Versands (im Auftrag der Warteschlange) und werden nirgends
 * im Klartext gespeichert. Gesperrte und abgemeldete Adressen bekommen nichts.
 */
final class ContactAccessSender
{
    private const TOPIC = 'team_mailings';

    public function __construct(private readonly AccessCredentials $credentials, private readonly MailSuppressions $suppressions, private readonly MailUnsubscribes $unsubscribes) {}

    public function canMail(TeamContact $contact): bool
    {
        return ! $this->suppressions->isSuppressed($contact->email) && ! $this->unsubscribes->isUnsubscribed($contact->email, self::TOPIC);
    }

    /** Reiht den Versand ein. @return bool false, wenn die Adresse nicht angeschrieben werden darf */
    public function queue(TeamContact $contact): bool
    {
        if (! $this->canMail($contact)) {
            return false;
        }

        SendContactAccessJob::dispatch($contact->id);

        return true;
    }

    /** Erzeugt den Zugang und sendet die Mail (im Auftrag der Warteschlange). */
    public function deliver(TeamContact $contact): bool
    {
        if (! $this->canMail($contact)) {
            return false;
        }

        $issued = $this->credentials->issue($contact);

        Mail::to($contact->email)->send(new ContactAccessMail(trim((string) $contact->first_name), $issued['link'], $issued['code'], $issued['credential']->code_hint, $this->unsubscribes->url($contact->email, self::TOPIC)));

        $issued['credential']->forceFill(['sent_at' => now()])->save();

        return true;
    }
}

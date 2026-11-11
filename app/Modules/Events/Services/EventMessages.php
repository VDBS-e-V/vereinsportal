<?php

namespace App\Modules\Events\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Events\Mail\EventMessageMail;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventMessage;
use DomainException;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Mitteilungen an die Angemeldeten einer Veranstaltung. Je E-Mail-Adresse geht nur eine Nachricht raus (auch wenn eine Lehrkraft ihre ganze Klasse angemeldet hat).
 * Gespeichert werden Betreff, Text, Empfängergruppe und Zahl der Empfänger, nie die Empfängerliste. Das allgemeine Protokoll nennt nur Veranstaltung und Zahl.
 */
final class EventMessages
{
    /** Anzahl der Adressen, die die Mitteilung bekämen. */
    public function recipientCount(Event $event, string $audience): int
    {
        return $this->emails($event, $audience)->count();
    }

    /** @throws DomainException */
    public function send(Event $event, string $subject, string $body, string $audience, User $by): EventMessage
    {
        $emails = $this->emails($event, $audience);

        if ($emails->isEmpty()) {
            throw new DomainException('Für diese Gruppe gibt es noch keine bestätigten Anmeldungen.');
        }

        $url = route('events.public.show', $event->slug);

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new EventMessageMail($event, trim($subject), trim($body), $url));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $message = $event->messages()->create(['subject' => trim($subject), 'body' => trim($body), 'audience' => $audience, 'recipients_count' => $emails->count(), 'sent_by_user_id' => $by->id]);

        AuditLog::record(AuditEvent::EventMessageSent, $by, null, ['veranstaltung' => $event->title, 'empfaenger' => $emails->count()]);

        return $message;
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function emails(Event $event, string $audience)
    {
        $query = $event->registrations()->whereNotNull('verified_at');

        match ($audience) {
            'waitlist' => $query->where('waitlisted', true),
            'both' => null,
            default => $query->where('waitlisted', false),
        };

        return $query->pluck('email')->map(fn (string $email): string => mb_strtolower($email))->unique()->values();
    }
}

<?php

namespace App\Modules\Tickets\Support;

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\Tickets\Mail\TicketMail;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * E-Mail-Hinweise zu Tickets. Die anfragende Person bekommt Antworten des Teams immer (sie hat das Ticket angelegt); das Team kann seine Hinweise
 * unter „Benachrichtigungen“ ausschalten (Eintrag „tickets_team“). Die Mails enthalten nie den Nachrichtentext, nur den Link ins Portal.
 * Ein Fehler beim Senden bricht nie die Aktion ab.
 */
final class TicketNotifier
{
    public function __construct(private readonly NotificationPreferences $preferences) {}

    /** Neues Ticket: an alle Bearbeitenden. */
    public function created(Ticket $ticket, User $by): void
    {
        $this->toHandlers($ticket, 'new', TicketService::name($by), $by);
    }

    /** Antwort des Teams: an die anfragende Person. */
    public function teamReplied(Ticket $ticket, User $by): void
    {
        $requester = $ticket->requester;

        if ($requester !== null && filled($requester->email) && $requester->id !== $by->id) {
            $this->send($requester, new TicketMail('reply', $ticket->reference(), $ticket->subject, route('support.show', $ticket)));
        }
    }

    /** Antwort der anfragenden Person: an die Bearbeiter:in, ohne Zuweisung an alle Bearbeitenden. */
    public function requesterReplied(Ticket $ticket, User $by): void
    {
        $assignee = $ticket->assignee;

        if ($assignee !== null) {
            $this->toUsers([$assignee], $ticket, 'requester_reply', TicketService::name($by), $by);

            return;
        }

        $this->toHandlers($ticket, 'requester_reply', TicketService::name($by), $by);
    }

    /** Zuweisung: an die neue Bearbeiter:in (nicht, wenn sie sich selbst zuweist). */
    public function assigned(Ticket $ticket, User $assignee, User $by): void
    {
        if ($assignee->id !== $by->id) {
            $this->toUsers([$assignee], $ticket, 'assigned', TicketService::name($by), $by);
        }
    }

    private function toHandlers(Ticket $ticket, string $kind, string $name, User $by): void
    {
        $ids = array_keys(TicketService::handlers());

        $this->toUsers($ids === [] ? [] : User::query()->whereIn('id', $ids)->get()->all(), $ticket, $kind, $name, $by);
    }

    /** @param  iterable<User>  $users */
    private function toUsers(iterable $users, Ticket $ticket, string $kind, string $name, User $by): void
    {
        foreach ($this->preferences->filter($users, 'tickets_team') as $user) {
            if ($user->id !== $by->id && filled($user->email)) {
                $this->send($user, new TicketMail($kind, $ticket->reference(), $ticket->subject, route('support.show', $ticket), $name));
            }
        }
    }

    private function send(User $user, TicketMail $mail): void
    {
        try {
            Mail::to($user->email)->send($mail);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

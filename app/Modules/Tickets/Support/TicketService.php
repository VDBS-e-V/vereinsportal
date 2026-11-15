<?php

namespace App\Modules\Tickets\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketEvent;
use App\Modules\Tickets\Models\TicketMessage;
use Illuminate\Support\Facades\DB;

/** Tickets anlegen, beantworten und bearbeiten. Jede Änderung steht im Verlauf des Tickets und im Protokoll des Portals (ohne Nachrichtentext). */
final class TicketService
{
    /** Wer Tickets bearbeitet (Berechtigung tickets.handle) und zuweisbar ist: Nutzer-ID => Name. @return array<int, string> */
    public static function handlers(): array
    {
        $keys = Capabilities::roleKeysWith(Capability::TicketsHandle);

        return User::query()
            ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->whereIn('key', $keys)))
            ->with('person')->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => self::name($user)])
            ->sort()->all();
    }

    public static function name(?User $user): string
    {
        return $user === null ? 'Gelöschtes Konto' : ($user->person?->fullName() ?: $user->email);
    }

    public static function canView(User $user, Ticket $ticket): bool
    {
        return $ticket->requester_id === $user->id || $user->can('tickets.handle');
    }

    /** @param array{subject: string, category: string, priority?: string, message: string} $data */
    public function create(User $requester, array $data): Ticket
    {
        return DB::transaction(function () use ($requester, $data): Ticket {
            $ticket = Ticket::query()->create([
                'subject' => trim($data['subject']), 'category' => $data['category'], 'priority' => $data['priority'] ?? 'normal',
                'status' => 'open', 'requester_id' => $requester->id, 'last_activity_at' => now(),
            ]);

            TicketMessage::query()->create(['ticket_id' => $ticket->id, 'user_id' => $requester->id, 'body' => trim($data['message']), 'is_internal' => false]);
            $this->event($ticket, $requester, 'created');
            AuditLog::record(AuditEvent::TicketCreated, $requester, null, ['ticket' => $ticket->reference()]);

            return $ticket;
        });
    }

    /** Nachricht im Ticket. Antwort des Teams: Offen wird „In Bearbeitung“; Antwort der anfragenden Person: „Wartet“ oder „Gelöst“ wird wieder „In Bearbeitung“ bzw. „Offen“. */
    public function post(Ticket $ticket, User $author, string $body, bool $internal = false): TicketMessage
    {
        return DB::transaction(function () use ($ticket, $author, $body, $internal): TicketMessage {
            $message = TicketMessage::query()->create(['ticket_id' => $ticket->id, 'user_id' => $author->id, 'body' => trim($body), 'is_internal' => $internal]);
            $ticket->last_activity_at = now();

            if (! $internal) {
                $isTeam = $author->id !== $ticket->requester_id && $author->can('tickets.handle');

                if ($isTeam && $ticket->status === 'open') {
                    $this->changeStatus($ticket, $author, 'in_progress');
                } elseif (! $isTeam && in_array($ticket->status, ['waiting', 'resolved', 'closed'], true)) {
                    $this->changeStatus($ticket, $author, $ticket->assignee_id !== null ? 'in_progress' : 'open');
                }
            }

            $ticket->save();
            AuditLog::record(AuditEvent::TicketMessagePosted, $author, null, ['ticket' => $ticket->reference(), 'intern' => $internal]);

            return $message;
        });
    }

    /** Status, Priorität und Bearbeitung ändern; nur echte Änderungen werden festgehalten. */
    public function update(Ticket $ticket, User $actor, ?string $status, ?string $priority, ?int $assigneeId, bool $assigneeGiven = true): void
    {
        DB::transaction(function () use ($ticket, $actor, $status, $priority, $assigneeId, $assigneeGiven): void {
            if ($assigneeGiven && $assigneeId !== $ticket->assignee_id) {
                $name = $assigneeId !== null ? self::name(User::query()->with('person')->find($assigneeId)) : null;
                $ticket->assignee_id = $assigneeId;
                $this->event($ticket, $actor, 'assigned', ['to' => $name]);
                AuditLog::record(AuditEvent::TicketAssigned, $actor, null, ['ticket' => $ticket->reference(), 'an' => $name]);

                // Ein offenes Ticket mit Bearbeiter:in ist in Bearbeitung.
                if ($assigneeId !== null && $ticket->status === 'open' && $status === null) {
                    $this->changeStatus($ticket, $actor, 'in_progress');
                }
            }

            if ($status !== null) {
                $this->changeStatus($ticket, $actor, $status);
            }

            if ($priority !== null && $priority !== $ticket->priority) {
                $this->event($ticket, $actor, 'priority', ['from' => Ticket::PRIORITIES[$ticket->priority] ?? $ticket->priority, 'to' => Ticket::PRIORITIES[$priority] ?? $priority]);
                $ticket->priority = $priority;
            }

            $ticket->last_activity_at = now();
            $ticket->save();
        });
    }

    public function changeStatus(Ticket $ticket, User $actor, string $status): void
    {
        if ($status === $ticket->status) {
            return;
        }

        $this->event($ticket, $actor, 'status', ['from' => Ticket::STATUSES[$ticket->status] ?? $ticket->status, 'to' => Ticket::STATUSES[$status] ?? $status]);
        AuditLog::record(AuditEvent::TicketStatusChanged, $actor, null, ['ticket' => $ticket->reference(), 'status' => $status]);

        $ticket->status = $status;
        $ticket->closed_at = $status === 'closed' ? now() : null;
        $ticket->save();
    }

    /** @param array<string, mixed> $data */
    private function event(Ticket $ticket, User $actor, string $type, array $data = []): void
    {
        TicketEvent::query()->create(['ticket_id' => $ticket->id, 'user_id' => $actor->id, 'type' => $type, 'data' => $data ?: null]);
    }
}

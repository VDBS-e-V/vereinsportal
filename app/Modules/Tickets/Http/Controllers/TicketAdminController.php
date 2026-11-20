<?php

namespace App\Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Support\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Alle Tickets für das Bearbeitungsteam: Warteschlange mit Filtern und Mehrfachauswahl, Ticket bearbeiten. Berechtigung tickets.handle. */
class TicketAdminController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'all', ...array_keys(Ticket::STATUSES)])], 'assignee' => ['nullable', 'string', 'max:20'],
            'priority' => ['nullable', Rule::in(array_keys(Ticket::PRIORITIES))], 'q' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $filters['status'] ?? 'active';
        $assignee = $filters['assignee'] ?? '';
        $q = trim((string) ($filters['q'] ?? ''));

        $tickets = Ticket::query()->with(['requester.person', 'assignee.person'])
            ->when($status === 'active', fn ($query) => $query->whereNotIn('status', ['resolved', 'closed']))
            ->when(isset(Ticket::STATUSES[$status]), fn ($query) => $query->where('status', $status))
            ->when($assignee === 'me', fn ($query) => $query->where('assignee_id', $this->user()->id))
            ->when($assignee === 'none', fn ($query) => $query->whereNull('assignee_id'))
            ->when(ctype_digit($assignee), fn ($query) => $query->where('assignee_id', (int) $assignee))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($q !== '', fn ($query) => $query->where('subject', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->orderByRaw("case priority when 'high' then 0 when 'normal' then 1 else 2 end")->orderByDesc('last_activity_at')->limit(300)->get();

        return view('tickets-admin.index', [
            'tickets' => $tickets, 'filters' => ['status' => $status, 'assignee' => $assignee, 'priority' => $filters['priority'] ?? '', 'q' => $q],
            'handlers' => TicketService::handlers(), 'statuses' => Ticket::STATUSES, 'priorities' => Ticket::PRIORITIES,
        ]);
    }

    /** Ein Ticket: Bearbeiter:in, Status und Priorität. */
    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Ticket::STATUSES))], 'priority' => ['nullable', Rule::in(array_keys(Ticket::PRIORITIES))], 'assignee_id' => ['nullable', 'integer'],
        ]);

        $assignee = filled($data['assignee_id'] ?? null) ? (int) $data['assignee_id'] : null;

        if ($assignee !== null && ! array_key_exists($assignee, TicketService::handlers())) {
            throw ValidationException::withMessages(['assignee_id' => 'Diese Person kann keine Tickets bearbeiten.']);
        }

        $this->tickets->update($ticket, $this->user(), $data['status'] ?? null, $data['priority'] ?? null, $assignee, $request->has('assignee_id'));

        return redirect()->to(route('support.show', $ticket).'#bearbeitung')->with('success', 'Das Ticket wurde gespeichert.');
    }

    /** Mehrfachauswahl: mir zuweisen, Zuweisung aufheben, Status ändern. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer'],
            'aktion' => ['required', Rule::in(['assign_me', 'unassign', 'status'])], 'status' => ['nullable', Rule::in(array_keys(Ticket::STATUSES))],
        ], ['ids.required' => 'Bitte wählen Sie mindestens ein Ticket aus.']);

        if ($data['aktion'] === 'status' && empty($data['status'])) {
            throw ValidationException::withMessages(['status' => 'Bitte wählen Sie einen Status.']);
        }

        $user = $this->user();
        $count = 0;

        foreach (Ticket::query()->whereIn('id', $data['ids'])->get() as $ticket) {
            match ($data['aktion']) {
                'assign_me' => $this->tickets->update($ticket, $user, null, null, $user->id),
                'unassign' => $this->tickets->update($ticket, $user, null, null, null),
                default => $this->tickets->update($ticket, $user, $data['status'], null, null, false),
            };
            $count++;
        }

        return redirect()->back()->with('success', $count === 1 ? 'Ein Ticket wurde geändert.' : $count.' Tickets wurden geändert.');
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}

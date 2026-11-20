@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Tickets']];
    $statusTone = fn (string $status): string => match ($status) { 'open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', default => 'neutral' };
    $statusFilter = ['active' => 'Offene (ohne gelöste und geschlossene)', 'all' => 'Alle'] + $statuses;
    $assigneeOptions = ['me' => 'Mir zugewiesen', 'none' => 'Nicht zugewiesen'] + $handlers;
@endphp

<x-layouts.app title="Tickets" width="full">
    <x-vdbs.layout.container>
        <x-vdbs.layout.page-header title="Tickets" lead="Anfragen und Meldungen aller Personen. Öffnen Sie ein Ticket, um zu antworten, oder weisen Sie mehrere auf einmal zu." :breadcrumb="$crumbs" />

        <form method="get" action="{{ route('tickets-admin.index') }}" class="vdbs-task-filter" aria-label="Tickets suchen und filtern">
            <x-vdbs.forms.input name="q" label="Suchen" type="search" :optional="true" maxlength="100" :value="$filters['q']" />
            <x-vdbs.forms.select name="status" label="Status" :options="$statusFilter" :value="$filters['status']" />
            <x-vdbs.forms.select name="assignee" label="Bearbeitung" :optional="true" placeholder="Alle" :options="$assigneeOptions" :value="$filters['assignee']" />
            <x-vdbs.forms.select name="priority" label="Priorität" :optional="true" placeholder="Alle" :options="$priorities" :value="$filters['priority']" />
            <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Filtern</x-vdbs.actions.button>
        </form>

        @if ($tickets->isEmpty())
            <x-vdbs.feedback.empty-state title="Keine Tickets" icon="ticket"><p>Zu diesem Filter gibt es keine Tickets.</p></x-vdbs.feedback.empty-state>
        @else
            <form id="tickets-form" method="post" action="{{ route('tickets-admin.bulk') }}">
                @csrf
                <x-vdbs.data.table caption="Tickets" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'tickets-form'], ['key' => 'ref', 'label' => 'Nummer'], ['key' => 'subject', 'label' => 'Betreff'], ['key' => 'who', 'label' => 'Von'], ['key' => 'status', 'label' => 'Status'], ['key' => 'prio', 'label' => 'Priorität'], ['key' => 'assignee', 'label' => 'Bearbeitung'], ['key' => 'last', 'label' => 'Letzte Aktivität']]">
                    @foreach ($tickets as $ticket)
                        <tr>
                            <x-vdbs.data.table-check name="ids[]" :value="$ticket->id" :label="$ticket->reference().' auswählen'" />
                            <td>{{ $ticket->reference() }}</td>
                            <td><a href="{{ route('support.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                            <td>{{ \App\Modules\Tickets\Support\TicketService::name($ticket->requester) }}</td>
                            <td><x-vdbs.data.badge :tone="$statusTone($ticket->status)">{{ $ticket->statusLabel() }}</x-vdbs.data.badge></td>
                            <td>{{ $priorities[$ticket->priority] ?? $ticket->priority }}</td>
                            <td>{{ $ticket->assignee ? \App\Modules\Tickets\Support\TicketService::name($ticket->assignee) : '–' }}</td>
                            <td>{{ $ticket->last_activity_at?->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
                <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="tickets-form">Nichts ausgewählt.</p>
                @error('ids')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                @error('status')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                <fieldset class="vdbs-fieldset">
                    <legend class="vdbs-fieldset__legend">Mit der Auswahl</legend>
                    <div class="vdbs-btn-group">
                        <x-vdbs.actions.button type="submit" name="aktion" value="assign_me" icon="user">Mir zuweisen</x-vdbs.actions.button>
                        <x-vdbs.actions.button type="submit" name="aktion" value="unassign" variant="secondary">Zuweisung aufheben</x-vdbs.actions.button>
                    </div>
                    <x-vdbs.forms.select name="status" label="Status setzen" :optional="true" placeholder="Status wählen" :options="$statuses" />
                    <x-vdbs.actions.button type="submit" name="aktion" value="status" variant="secondary">Status ändern</x-vdbs.actions.button>
                </fieldset>
            </form>
        @endif
    </x-vdbs.layout.container>
</x-layouts.app>

@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto', 'href' => route('account.home')], ['label' => 'Meine Tickets']];
    $statusTone = fn (string $status): string => match ($status) { 'open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', default => 'neutral' };
@endphp

<x-layouts.app title="Meine Tickets" width="wide">
    <x-vdbs.layout.page-header title="Meine Tickets" lead="Ihre Anfragen und Meldungen an das Portal-Team. Im Ticket schreiben Sie direkt mit den Bearbeitenden." :breadcrumb="$crumbs">
        <x-slot:actions><x-vdbs.actions.button :href="route('support.create')" icon="plus">Neues Ticket</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($tickets->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Tickets" icon="ticket">
            <p>Wenn Sie Hilfe brauchen oder etwas nicht funktioniert, legen Sie ein Ticket an.</p>
            <x-slot:actions><x-vdbs.actions.button :href="route('support.create')">Neues Ticket</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Meine Tickets" :columns="[['key' => 'ref', 'label' => 'Nummer'], ['key' => 'subject', 'label' => 'Betreff'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'who', 'label' => 'Bearbeitung'], ['key' => 'last', 'label' => 'Letzte Aktivität']]">
            @foreach ($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->reference() }}</td>
                    <td><a href="{{ route('support.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                    <td><x-vdbs.data.badge :tone="$statusTone($ticket->status)">{{ $ticket->statusLabel() }}</x-vdbs.data.badge></td>
                    <td>{{ $ticket->assignee ? \App\Modules\Tickets\Support\TicketService::name($ticket->assignee) : 'noch nicht zugewiesen' }}</td>
                    <td>{{ $ticket->last_activity_at?->format('d.m.Y H:i') }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>

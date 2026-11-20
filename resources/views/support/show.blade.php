@php
    use App\Modules\Tickets\Support\TicketService;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto', 'href' => route('account.home')], ['label' => 'Meine Tickets', 'href' => route('support.mine')], ['label' => $ticket->reference()]];
    $statusTone = fn (string $status): string => match ($status) { 'open' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', default => 'neutral' };
@endphp

<x-layouts.app :title="$ticket->reference().' '.$ticket->subject" width="wide">
    <x-vdbs.layout.page-header :title="$ticket->subject" :lead="'Ticket '.$ticket->reference().' · '.(\App\Modules\Tickets\Models\Ticket::CATEGORIES[$ticket->category] ?? $ticket->category)" :breadcrumb="$crumbs">
        <x-slot:actions>
            @if ($handler)<x-vdbs.actions.button variant="secondary" :href="route('tickets-admin.index')" icon="ticket">Alle Tickets</x-vdbs.actions.button>@endif
            @if ($isRequester && ! $ticket->isClosed())
                <form method="post" action="{{ route('support.close', $ticket) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary" icon="check">Ticket schließen</x-vdbs.actions.button></form>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="3">
        <x-vdbs.data.stat label="Stand" :value="$ticket->statusLabel()" />
        <x-vdbs.data.stat label="Bearbeitung" :value="$ticket->assignee ? TicketService::name($ticket->assignee) : 'noch nicht zugewiesen'" />
        <x-vdbs.data.stat label="Angelegt von" :value="TicketService::name($ticket->requester)" :hint="$ticket->created_at?->format('d.m.Y H:i')" />
    </x-vdbs.layout.grid>

    @if ($handler)
        <section id="bearbeitung" aria-labelledby="edit-heading" style="margin-top:var(--space-8)">
            <h2 id="edit-heading" style="font-size:var(--font-size-h3)">Bearbeitung</h2>
            <x-vdbs.forms.form :action="route('tickets-admin.update', $ticket)" method="put" :summary="false">
                <x-vdbs.forms.form-grid :columns="3">
                    <x-vdbs.forms.select name="assignee_id" label="Bearbeiter:in" :optional="true" placeholder="Niemand" :options="$handlers" :value="old('assignee_id', $ticket->assignee_id)" />
                    <x-vdbs.forms.select name="status" label="Status" :options="$statuses" :value="old('status', $ticket->status)" />
                    <x-vdbs.forms.select name="priority" label="Priorität" :options="$priorities" :value="old('priority', $ticket->priority)" />
                </x-vdbs.forms.form-grid>
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        </section>
    @endif

    <section id="verlauf" aria-labelledby="chat-heading" style="margin-top:var(--space-8)">
        <h2 id="chat-heading" style="font-size:var(--font-size-h3)">Verlauf und Nachrichten</h2>

        <ol class="vdbs-ticket-thread">
            @foreach ($timeline as $row)
                @if ($row['kind'] === 'message')
                    @php $message = $row['item']; $mine = $message->user_id === auth()->id(); @endphp
                    <li class="vdbs-ticket-message @if ($mine) vdbs-ticket-message--mine @endif @if ($message->is_internal) vdbs-ticket-message--internal @endif">
                        <p class="vdbs-ticket-message__meta"><strong>{{ TicketService::name($message->author) }}</strong> · {{ $message->created_at?->format('d.m.Y H:i') }}@if ($message->is_internal) · <x-vdbs.data.badge tone="warning">Interne Notiz</x-vdbs.data.badge>@endif</p>
                        <p class="vdbs-ticket-message__body">{!! nl2br(e($message->body)) !!}</p>
                        @if ($message->attachments->isNotEmpty())
                            <ul class="vdbs-ticket-message__files">
                                @foreach ($message->attachments as $attachment)
                                    <li><a href="{{ route('support.attachments.download', [$ticket, $attachment]) }}">{{ $attachment->original_name }}</a> <span class="vdbs-hint">({{ $attachment->sizeLabel() }})</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @else
                    <li class="vdbs-ticket-event">{{ $row['at']?->format('d.m.Y H:i') }} · {{ TicketService::name($row['item']->actor) }}: {{ $row['item']->describe() }}</li>
                @endif
            @endforeach
        </ol>

        @if ($ticket->isClosed() && ! $handler)
            <x-vdbs.feedback.alert variant="info">Dieses Ticket ist geschlossen. Wenn Sie schreiben, wird es wieder geöffnet.</x-vdbs.feedback.alert>
        @endif

        <x-vdbs.forms.form :action="route('support.message', $ticket)" :files="true" :summary="false">
            <x-vdbs.forms.textarea name="body" label="Ihre Nachricht" :required="true" maxlength="10000" :rows="5" />
            @error('files')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
            <x-vdbs.forms.file name="files" label="Anhänge" :multiple="true" :optional="true" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.odt,.ods,.zip" hint="PDF, Word, Excel, CSV, OpenDocument, ZIP oder Text. Bilder sind nicht erlaubt. Bis zu fünf Dateien." />
            @if ($handler)<x-vdbs.forms.checkbox name="internal" label="Nur intern (die anfragende Person sieht die Notiz nicht)" :checked="(bool) old('internal')" />@endif
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="send">Senden</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>
</x-layouts.app>

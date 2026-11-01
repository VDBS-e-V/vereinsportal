@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Veranstaltungen', 'href' => route('events.admin.index')], ['label' => $event->title]];
@endphp

<x-layouts.app :title="$event->title" width="wide">
    <x-vdbs.layout.page-header :title="$event->title" :breadcrumb="$crumbs">
        <x-slot:meta><x-vdbs.data.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-vdbs.data.badge></x-slot:meta>
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('events.public.show', $event->slug)" icon="eye">Öffentliche Seite</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('events.admin.export', $event)" icon="download">Liste als CSV</x-vdbs.actions.button>
            @if ($canManage)
                <x-vdbs.actions.button variant="secondary" :href="route('events.admin.import', $event)" icon="upload">Liste importieren</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('events.admin.messages.create', $event)" icon="mail">Mitteilung senden</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('events.admin.edit', $event)" icon="pencil">Bearbeiten</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    <p>{{ $confirmed }} bestätigte Anmeldungen{{ $event->capacity ? ' von '.$event->capacity.' Plätzen' : '' }}{{ $waitlist > 0 ? ', '.$waitlist.' auf der Warteliste' : '' }}{{ $total > $confirmed + $waitlist ? ', '.($total - $confirmed - $waitlist).' noch nicht bestätigt' : '' }}.</p>

    <section aria-labelledby="sessions-heading" style="margin-top:var(--space-6)">
        <h2 id="sessions-heading" style="font-size:var(--font-size-h3)">Termine und Anwesenheit</h2>

        @if ($event->sessions->isEmpty())
            <p>Noch kein Termin eingetragen.</p>
        @else
            <x-vdbs.data.table caption="Termine" :columns="[['key' => 'period', 'label' => 'Zeitraum'], ['key' => 'location', 'label' => 'Ort'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($event->sessions as $session)
                    <tr>
                        <td>{{ $session->period() }}</td>
                        <td>{{ $session->location ?? $event->location ?? '–' }}</td>
                        <td>
                            <div class="vdbs-btn-group">
                                <x-vdbs.actions.button size="s" :href="route('events.admin.attendance', [$event, $session])" icon="list-checks">Anwesenheit</x-vdbs.actions.button>
                                @if ($canManage)
                                    <form method="post" action="{{ route('events.admin.sessions.destroy', [$event, $session]) }}">@csrf @method('delete')<x-vdbs.actions.button type="submit" size="s" variant="danger" shape="outline">Entfernen</x-vdbs.actions.button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif

        @if ($canManage)
            <x-vdbs.forms.form :action="route('events.admin.sessions.store', $event)" :summary="false" style="margin-top:var(--space-5)">
                <x-vdbs.forms.form-grid :columns="3">
                    <x-vdbs.forms.input name="date" label="Datum" type="date" :required="true" />
                    <x-vdbs.forms.input name="start" label="Beginn" type="time" :required="true" value="09:00" />
                    <x-vdbs.forms.input name="end" label="Ende" type="time" :required="true" value="15:00" />
                    <x-vdbs.forms.input name="location" label="Ort" :optional="true" />
                    <x-vdbs.forms.input name="notes" label="Hinweis" :optional="true" />
                </x-vdbs.forms.form-grid>
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="plus">Termin eintragen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    </section>

    @if ($bySchool->count() > 1 || ($bySchool->count() === 1 && $total > 0))
        <section aria-labelledby="schools-heading" style="margin-top:var(--space-8)">
            <h2 id="schools-heading" style="font-size:var(--font-size-h3)">Anmeldungen nach Schule</h2>
            <x-vdbs.data.table caption="Anmeldungen nach Schule" :columns="[['key' => 'school', 'label' => 'Schule'], ['key' => 'total', 'label' => 'Bestätigt'], ['key' => 'waiting', 'label' => 'Davon Warteliste']]">
                @foreach ($bySchool as $row)
                    <tr><td>{{ $row->school }}</td><td>{{ $row->total - $row->waiting }}</td><td>{{ $row->waiting }}</td></tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endif

    <section aria-labelledby="regs-heading" style="margin-top:var(--space-8)">
        <h2 id="regs-heading" style="font-size:var(--font-size-h3)">Anmeldungen</h2>

        <form method="get" role="search" style="margin-bottom:var(--space-4)">
            <x-vdbs.forms.input name="q" label="Suchen (Name, Schule, Klasse, Ticketcode)" :value="$search" :optional="true" />
            <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Suchen</x-vdbs.actions.button>
        </form>

        @if ($registrations->isEmpty())
            <p>Keine Anmeldungen gefunden.</p>
        @else
            <x-vdbs.data.table caption="Anmeldungen" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'class', 'label' => 'Klasse'], ['key' => 'ticket', 'label' => 'Ticket'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'present', 'label' => 'Anwesend'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($registrations as $row)
                    <tr>
                        <td>{{ $row->name }}<br><small>{{ $row->email }}</small></td>
                        <td>{{ $row->school }}</td>
                        <td>{{ $row->class_name }}</td>
                        <td><code>{{ $row->ticket() }}</code></td>
                        <td>@if ($row->isConfirmed() && $row->waitlisted)<x-vdbs.data.badge tone="info">Warteliste</x-vdbs.data.badge>@elseif ($row->isConfirmed())<x-vdbs.data.badge tone="success">Bestätigt</x-vdbs.data.badge>@else<x-vdbs.data.badge tone="warning">Offen</x-vdbs.data.badge>@endif @if ($row->source === 'upload')<x-vdbs.data.badge>Liste</x-vdbs.data.badge>@endif</td>
                        <td>{{ $row->waitlisted ? '–' : $row->attendances_count.' von '.$event->sessions->count() }}</td>
                        <td>
                            <div class="vdbs-btn-group">
                                @if ($row->attendances_count > 0)
                                    <x-vdbs.actions.button size="s" variant="tertiary" :href="route('events.admin.registrations.certificate', [$event, $row])">Bestätigung</x-vdbs.actions.button>
                                @endif
                                @if ($canManage)
                                    <form method="post" action="{{ route('events.admin.registrations.destroy', [$event, $row]) }}">@csrf @method('delete')<x-vdbs.actions.button type="submit" size="s" variant="danger" shape="outline">Entfernen</x-vdbs.actions.button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>

            <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$registrations" /></div>
        @endif
    </section>

    @if ($event->messages->isNotEmpty())
        <section aria-labelledby="messages-heading" style="margin-top:var(--space-8)">
            <h2 id="messages-heading" style="font-size:var(--font-size-h3)">Gesendete Mitteilungen</h2>
            <x-vdbs.data.table caption="Gesendete Mitteilungen" :columns="[['key' => 'subject', 'label' => 'Betreff'], ['key' => 'audience', 'label' => 'An'], ['key' => 'count', 'label' => 'Adressen'], ['key' => 'date', 'label' => 'Gesendet']]">
                @foreach ($event->messages as $message)
                    <tr>
                        <td>{{ $message->subject }}</td>
                        <td>{{ \App\Modules\Events\Models\EventMessage::AUDIENCES[$message->audience] ?? $message->audience }}</td>
                        <td>{{ $message->recipients_count }}</td>
                        <td>{{ $message->created_at->format('d.m.Y, H:i') }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endif

    @if ($canManage && ! $event->registrations()->exists())
        <section style="margin-top:var(--space-8)">
            <form method="post" action="{{ route('events.admin.destroy', $event) }}">@csrf @method('delete')<x-vdbs.actions.button type="submit" variant="danger" shape="outline" icon="trash-2">Veranstaltung löschen</x-vdbs.actions.button></form>
        </section>
    @endif
</x-layouts.app>

@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Bildungsreferenten'], ['label' => 'Übersicht']];
@endphp

<x-layouts.app title="Bildungsreferenten" width="wide">
    <x-vdbs.layout.page-header title="Übersicht für Bildungsreferenten" lead="Seminare und Veranstaltungen im Überblick. Sie können alles ansehen, ändern kann die Koordination." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('seminars.index')" icon="list">Alle Seminare</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('events.admin.index')" icon="calendar">Alle Veranstaltungen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @include('portal._tasks', ['area' => \App\Support\PortalAreas::Bildungsreferenten])


    <h2 style="font-size:var(--font-size-h3)">Seminare nach Stand</h2>
    <x-vdbs.layout.grid :columns="4">
        @foreach (\App\Modules\Seminars\Enums\SeminarStatus::cases() as $status)
            <x-vdbs.data.stat :label="$status->label()" :value="$byStatus[$status->value]" :tone="$status->tone() === 'neutral' ? 'neutral' : $status->tone()" :href="route('seminars.index', ['status' => $status->value])" />
        @endforeach
    </x-vdbs.layout.grid>

    <section aria-labelledby="sessions-heading" style="margin-top:var(--space-8)">
        <h2 id="sessions-heading" style="font-size:var(--font-size-h3)">Nächste Seminartermine</h2>

        @if ($sessions->isEmpty())
            <p>Es stehen keine Termine an.</p>
        @else
            <x-vdbs.data.table caption="Nächste Seminartermine" :columns="[['key' => 'date', 'label' => 'Termin'], ['key' => 'seminar', 'label' => 'Seminar'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'status', 'label' => 'Stand']]">
                @foreach ($sessions as $session)
                    <tr>
                        <td>{{ $session->period() }}</td>
                        <td><a href="{{ route('seminars.show', $session->seminar) }}">{{ $session->seminar->title }}</a></td>
                        <td>{{ $session->seminar->partner->name }}</td>
                        <td><x-vdbs.data.badge :tone="$session->seminar->status->tone()">{{ $session->seminar->status->label() }}</x-vdbs.data.badge></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>

    <section aria-labelledby="events-heading" style="margin-top:var(--space-8)">
        <h2 id="events-heading" style="font-size:var(--font-size-h3)">Veranstaltungen mit offener Anmeldung</h2>

        @if ($events->isEmpty())
            <p>Zurzeit gibt es keine.</p>
        @else
            <x-vdbs.data.table caption="Veranstaltungen" :columns="[['key' => 'title', 'label' => 'Veranstaltung'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'regs', 'label' => 'Anmeldungen']]">
                @foreach ($events as $event)
                    <tr>
                        <td><a href="{{ route('events.admin.show', $event) }}">{{ $event->title }}</a></td>
                        <td>{{ $event->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                        <td>{{ $event->confirmed_count }}{{ $event->capacity ? ' von '.$event->capacity : '' }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>
</x-layouts.app>

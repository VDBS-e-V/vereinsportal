@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Bildungsreferenten'], ['label' => 'Übersicht']];
@endphp

<x-layouts.app title="Bildungsreferenten" width="wide">
    <x-vdbs.layout.page-header title="Übersicht für Bildungsreferenten" lead="Veranstaltungen im Überblick. Sie können alles ansehen, ändern kann die Koordination." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('events.admin.index')" icon="calendar">Alle Veranstaltungen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @include('portal._tasks', ['area' => \App\Support\PortalAreas::Bildungsreferenten])


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

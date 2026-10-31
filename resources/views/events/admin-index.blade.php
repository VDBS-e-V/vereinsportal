@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Veranstaltungen']];
@endphp

<x-layouts.app title="Veranstaltungen" width="wide">
    <x-vdbs.layout.page-header title="Veranstaltungen" lead="Großveranstaltungen für Schülerinnen und Schüler mehrerer Schulen mit Anmeldung, Anwesenheit und Teilnahmebestätigung." :breadcrumb="$crumbs">
        @if ($canManage)
            <x-slot:actions><x-vdbs.actions.button :href="route('events.admin.create')" icon="plus">Veranstaltung anlegen</x-vdbs.actions.button></x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.data.toolbar label="Veranstaltungen filtern" :count="$events->total().' Veranstaltungen'" :active="[]" :reset-href="route('events.admin.index')">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$status" />
    </x-vdbs.data.toolbar>

    @if ($events->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Veranstaltungen" icon="calendar"><p>Es ist noch keine Veranstaltung angelegt.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Veranstaltungen" :columns="[['key' => 'title', 'label' => 'Titel'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'status', 'label' => 'Status'], ['key' => 'regs', 'label' => 'Anmeldungen']]">
            @foreach ($events as $row)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('events.admin.show', $row) }}">{{ $row->title }}</a></td>
                    <td>{{ $row->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$row->status->tone()">{{ $row->status->label() }}</x-vdbs.data.badge></td>
                    <td>{{ $row->confirmed_count }} bestätigt{{ $row->registrations_count > $row->confirmed_count ? ', '.($row->registrations_count - $row->confirmed_count).' offen' : '' }}{{ $row->capacity ? ' von '.$row->capacity : '' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$events" /></div>
    @endif
</x-layouts.app>

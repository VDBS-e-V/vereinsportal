@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare']];
    $total = $seminars->total();
    $count = $total === 1 ? '1 Seminar' : $total.' Seminare';
@endphp

<x-layouts.app title="Seminare" width="wide">
    <x-vdbs.layout.page-header title="Seminare" lead="Schulseminare planen: SV-Tage, SV-Coaching und SV-Wahl mit Terminen, Verlauf und Kostenvoranschlag." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('seminar-prices.index')" icon="clipboard-list">Preisliste</x-vdbs.actions.button>
            @if ($canManage)
                <x-vdbs.actions.button :href="route('seminars.create')" icon="plus">Seminar anlegen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="3" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Seminare" value="{{ $stats['total'] }}" icon="calendar" />
        <x-vdbs.data.stat label="Anfrage oder in Planung" value="{{ $stats['open'] }}" icon="clock" :tone="$stats['open'] > 0 ? 'warning' : 'neutral'" />
        <x-vdbs.data.stat label="Terminiert oder bestätigt" value="{{ $stats['planned'] }}" icon="check" tone="success" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Seminare filtern" :count="$count" :active="$active" :reset-href="route('seminars.index')" search-placeholder="Titel, Schule oder Ort suchen">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
        <x-vdbs.forms.select name="type" label="Leistung" placeholder="Alle" :options="$typeOptions" :value="$filters['type']" />
    </x-vdbs.data.toolbar>

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Seminare gefunden" icon="calendar">
            <p>@if ($stats['total'] === 0) Es sind noch keine Seminare erfasst. @else Zu den gewählten Filtern gibt es keine Seminare. Prüfen Sie die Schreibweise oder entfernen Sie Filter. @endif</p>
            <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('seminars.index')">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Seminare" :columns="[['key' => 'title', 'label' => 'Seminar'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'type', 'label' => 'Leistung'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'status', 'label' => 'Status']]">
            @foreach ($seminars as $row)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('seminars.show', $row) }}">{{ $row->title }}</a></td>
                    <td>{{ $row->partner->name }}</td>
                    <td>{{ $row->type->label() }}</td>
                    <td>{{ $row->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$row->status->tone()">{{ $row->status->label() }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$seminars" /></div>
    @endif
</x-layouts.app>

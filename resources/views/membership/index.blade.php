@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Mitgliedschaften']];
    $total = $memberships->total();
    $count = $total === 1 ? '1 Mitgliedschaft' : $total.' Mitgliedschaften';
@endphp

<x-layouts.app title="Mitgliedschaften" width="wide">
    <x-vdbs.layout.page-header title="Mitgliedschaften" lead="Anträge, Aufnahmen und Austritte. Diese Angaben sehen nur Mitglieder des Vorstands." :breadcrumb="$crumbs">
        @if ($canManage)
            <x-slot:actions>
                <x-vdbs.actions.button :href="route('membership.create')" icon="plus">Mitgliedschaft anlegen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="4" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Beantragt" value="{{ $stats['requested'] }}" icon="mail" :tone="$stats['requested'] > 0 ? 'warning' : 'neutral'" hint="Warten auf Entscheidung" />
        <x-vdbs.data.stat label="Aktiv" value="{{ $stats['active'] }}" icon="users" tone="success" />
        <x-vdbs.data.stat label="Austritt eingetragen" value="{{ $stats['ending'] }}" icon="clock" hint="Noch aktiv bis zum Austrittsdatum" />
        <x-vdbs.data.stat label="Beendet" value="{{ $stats['ended'] }}" icon="user" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Mitgliedschaften filtern" :count="$count" :active="$active" :reset-href="route('membership.index')" search-placeholder="Name suchen">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
    </x-vdbs.data.toolbar>

    @if ($memberships->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Mitgliedschaften gefunden" icon="search">
            <p>Zu den gewählten Filtern gibt es keine Mitgliedschaften. Prüfen Sie die Schreibweise oder entfernen Sie Filter.</p>
            <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('membership.index')">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Mitgliedschaften" :columns="$columns" :sort="$sort" :direction="$direction">
            @foreach ($memberships as $row)
                @php $state = $row->effectiveStatus(); @endphp
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('membership.show', $row) }}">{{ $row->displayName() }}</a>@if ($row->hasNoPerson()) <span class="vdbs-sr-only">(ohne Online-Konto)</span>@endif</td>
                    <td><x-vdbs.data.badge :tone="$state->tone()">{{ $state->label() }}</x-vdbs.data.badge>@if ($row->hasNoPerson()) <x-vdbs.data.badge>Kein Konto</x-vdbs.data.badge>@endif @if ($row->isEnding()) <x-vdbs.data.badge tone="warning">Austritt</x-vdbs.data.badge>@endif</td>
                    <td>{{ $row->requested_on?->format('d.m.Y') ?? '–' }}</td>
                    <td>{{ $row->starts_on?->format('d.m.Y') ?? '–' }}</td>
                    <td>{{ $row->ends_on?->format('d.m.Y') ?? '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$memberships" /></div>
    @endif
</x-layouts.app>

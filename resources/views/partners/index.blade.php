@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Schulen']];
    $total = $partners->total();
    $count = $total === 1 ? '1 Schule' : $total.' Schulen';
@endphp

<x-layouts.app title="Schulen" width="wide">
    <x-vdbs.layout.page-header title="Schulen" lead="Partnerschulen des Vereins mit Ansprechpersonen und Angaben zur Schule." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="$exportUrl" icon="download">Als CSV exportieren</x-vdbs.actions.button>
            @if ($canManage)
                <x-vdbs.actions.button variant="secondary" :href="route('partners.import')" icon="upload">CSV importieren</x-vdbs.actions.button>
                <x-vdbs.actions.button :href="route('partners.create')" icon="plus">Schule anlegen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="3" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Schulen" value="{{ $stats['total'] }}" icon="school" />
        <x-vdbs.data.stat label="Aktiv" value="{{ $stats['active'] }}" icon="check" tone="success" />
        <x-vdbs.data.stat label="Inaktiv" value="{{ $stats['inactive'] }}" icon="clock" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Schulen filtern" :count="$count" :active="$active" :reset-href="route('partners.index')" search-placeholder="Name, Ort, PLZ oder Ansprechperson suchen">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
        <x-vdbs.forms.select name="school_type" label="Schulart" placeholder="Alle" :options="$typeOptions" :value="$filters['school_type']" />
        <x-vdbs.forms.select name="federal_state" label="Bundesland" placeholder="Alle" :options="$stateOptions" :value="$filters['federal_state']" />
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
    </x-vdbs.data.toolbar>

    @if ($partners->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Schulen gefunden" icon="school">
            <p>@if ($stats['total'] === 0) Es sind noch keine Schulen erfasst. Legen Sie eine Schule an oder importieren Sie eine CSV-Datei. @else Zu den gewählten Filtern gibt es keine Schulen. Prüfen Sie die Schreibweise oder entfernen Sie Filter. @endif</p>
            <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('partners.index')">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Schulen" :columns="$columns" :sort="$sort" :direction="$direction">
            @foreach ($partners as $row)
                @php
                    $attributes = $row->getAttribute('attributes') ?? [];
                    $type = isset($attributes['school_type']) ? \App\Modules\Partners\Enums\SchoolType::tryFrom($attributes['school_type'])?->label() : null;
                    $state = isset($attributes['federal_state']) ? \App\Modules\Partners\Enums\FederalState::tryFrom($attributes['federal_state'])?->label() : null;
                @endphp
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('partners.show', $row) }}">{{ $row->name }}</a></td>
                    <td>{{ trim(($row->postal_code ?? '').' '.($row->city ?? '')) ?: '–' }}</td>
                    <td>{{ $type ?? '–' }}</td>
                    <td>{{ $state ?? '–' }}</td>
                    <td>{{ $row->contact_name ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$row->status->tone()">{{ $row->status->label() }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$partners" /></div>
    @endif
</x-layouts.app>

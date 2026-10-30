@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Zugangsanträge']];
    $total = $requests->total();
    $count = $total === 1 ? '1 Antrag' : $total.' Anträge';
@endphp

<x-layouts.app title="Zugangsanträge" width="wide">
    <x-vdbs.layout.page-header title="Zugangsanträge" lead="Anträge aus dem öffentlichen Formular „Zugang zum Portal“. Bei der Annahme entsteht ein Konto mit Einladung." :breadcrumb="$crumbs" />

    <x-vdbs.layout.grid :columns="3" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Neu" value="{{ $stats['new'] }}" icon="mail" :tone="$stats['new'] > 0 ? 'warning' : 'neutral'" />
        <x-vdbs.data.stat label="In Prüfung" value="{{ $stats['review'] }}" icon="clock" />
        <x-vdbs.data.stat label="Entschieden" value="{{ $stats['decided'] }}" icon="check" tone="success" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Zugangsanträge filtern" :count="$count" :active="$active" :reset-href="route('access-requests.index')" search-placeholder="Name, E-Mail oder Schule suchen">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
    </x-vdbs.data.toolbar>

    @if ($requests->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Anträge" icon="mail">
            <p>Zu den gewählten Filtern gibt es keine Zugangsanträge.</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Zugangsanträge" :columns="[['key' => 'date', 'label' => 'Eingegangen'], ['key' => 'name', 'label' => 'Name'], ['key' => 'connection', 'label' => 'Verbindung'], ['key' => 'organization', 'label' => 'Träger / Schule'], ['key' => 'status', 'label' => 'Status']]">
            @foreach ($requests as $row)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('access-requests.show', $row) }}">{{ $row->created_at?->format('d.m.Y') }}</a></td>
                    <td>{{ $row->fullName() }}</td>
                    <td>{{ $row->connection->label() }}</td>
                    <td>{{ $row->organization ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$row->status->tone()">{{ $row->status->label() }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$requests" /></div>
    @endif
</x-layouts.app>

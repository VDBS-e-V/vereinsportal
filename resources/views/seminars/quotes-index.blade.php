@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Kostenvoranschläge']];
    $total = $quotes->total();
    $count = $total === 1 ? '1 Kostenvoranschlag' : $total.' Kostenvoranschläge';
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format($cents);
@endphp

<x-layouts.app title="Kostenvoranschläge" width="wide">
    <x-vdbs.layout.page-header title="Kostenvoranschläge" lead="Pro Schule mehrere Positionen (Produkt mit Stückzahl), als Brief druckbar." :breadcrumb="$crumbs">
        <x-slot:actions>
            @if ($canManage)
                <x-vdbs.actions.button :href="route('seminar-quotes.create')" icon="plus">Kostenvoranschlag erstellen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.toolbar label="Kostenvoranschläge filtern" :count="$count" :active="$active" :reset-href="route('seminar-quotes.index')" search-placeholder="Schule oder Ort suchen" />

    @if ($quotes->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Kostenvoranschläge" icon="clipboard-list">
            <p>@if ($search) Zu dieser Suche gibt es keine Kostenvoranschläge. @else Es sind noch keine Kostenvoranschläge erstellt. @endif</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Kostenvoranschläge" :columns="[['key' => 'date', 'label' => 'Datum'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'positions', 'label' => 'Positionen'], ['key' => 'seminar', 'label' => 'Seminar'], ['key' => 'total', 'label' => 'Endsumme']]">
            @foreach ($quotes as $row)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('seminar-quotes.show', $row) }}">{{ $row->created_at?->format('d.m.Y') }}</a></td>
                    <td>{{ $row->partner->name }}</td>
                    <td>{{ collect($row->positions)->pluck('title')->implode(', ') }}</td>
                    <td>{{ $row->seminar?->title ?? '–' }}</td>
                    <td>{{ $money($row->total_cents) }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$quotes" /></div>
    @endif
</x-layouts.app>

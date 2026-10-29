@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Personen']];
    $total = $persons->total();
    $count = $total === 1 ? '1 Person' : $total.' Personen';
@endphp

<x-layouts.app title="Personen" width="wide">
    <x-vdbs.layout.page-header title="Personen" lead="Stammdaten der Mitglieder und Kontakte, unabhängig davon, ob sie ein Konto haben." :breadcrumb="$crumbs">
        @if ($canManage)
            <x-slot:actions>
                <x-vdbs.actions.button :href="route('admin.people.create')" icon="plus">Person anlegen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
        <x-slot:tabs><x-admin.nav current="people" /></x-slot:tabs>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="3" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Personen" value="{{ $stats['total'] }}" icon="users" />
        <x-vdbs.data.stat label="Mit Konto" value="{{ $stats['withAccount'] }}" icon="user" tone="success" />
        <x-vdbs.data.stat label="Ohne Konto" value="{{ $stats['withoutAccount'] }}" icon="mail" hint="Können noch nicht anmelden" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Personen filtern" :count="$count" :active="$active" :reset-href="route('admin.people.index')" search-placeholder="Name, E-Mail oder Ort suchen">
        <x-vdbs.forms.select name="account" label="Konto" placeholder="Alle" :options="$accountOptions" :value="$filters['account']" />
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
    </x-vdbs.data.toolbar>

    @if ($persons->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Personen gefunden" icon="search">
            <p>Zu den gewählten Filtern gibt es keine Personen. Prüfen Sie die Schreibweise oder entfernen Sie Filter.</p>
            <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('admin.people.index')">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Personen" :columns="$columns" :sort="$sort" :direction="$direction">
            @foreach ($persons as $row)
                <tr>
                    <td><a href="{{ route('admin.people.show', $row) }}">{{ $row->fullName() }}</a></td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->city ?? '–' }}</td>
                    <td>{{ $row->birth_date?->format('d.m.Y') }}</td>
                    <td>
                        @if ($row->user)
                            <x-vdbs.data.badge :tone="$row->user->status->tone()">{{ $row->user->status->label() }}</x-vdbs.data.badge>
                        @else
                            <span aria-hidden="true">–</span><span class="vdbs-sr-only">kein Konto</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$persons" /></div>
    @endif
</x-layouts.app>

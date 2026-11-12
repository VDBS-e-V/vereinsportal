@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Beschlüsse']];
    $tone = ['accepted' => 'success', 'rejected' => 'error', 'postponed' => 'warning'];
@endphp

<x-layouts.app title="Beschlüsse" width="wide">
    <x-vdbs.layout.page-header title="Beschlüsse des Vorstands" lead="Alle Beschlüsse mit fortlaufender Nummer je Jahr. Neue Beschlüsse erfassen Sie auf der Seite der Sitzung, in der sie gefasst wurden." :breadcrumb="$crumbs" />

    <form method="get" action="{{ route('board.resolutions.index') }}" class="vdbs-form">
        <x-vdbs.forms.input name="q" label="Suchen" type="search" :optional="true" maxlength="100" :value="$filters['q'] ?? ''" />
        <x-vdbs.forms.select name="year" label="Jahr" :optional="true" placeholder="Alle Jahre" :options="$years" :value="$filters['year'] ?? ''" />
        <x-vdbs.forms.select name="outcome" label="Ergebnis" :optional="true" placeholder="Alle" :options="$outcomes" :value="$filters['outcome'] ?? ''" />
        <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Filtern</x-vdbs.actions.button>
    </form>

    @if ($resolutions->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Beschlüsse" icon="file-text"><p>Es sind noch keine Beschlüsse erfasst oder keiner passt zum Filter.</p></x-vdbs.feedback.empty-state>
    @else
        <form id="resolutions-form" method="post" action="{{ route('board.resolutions.destroy-many') }}">
            @csrf
            <x-vdbs.data.table caption="Beschlüsse" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'resolutions-form'], ['key' => 'number', 'label' => 'Nummer'], ['key' => 'title', 'label' => 'Beschluss'], ['key' => 'date', 'label' => 'Sitzung'], ['key' => 'outcome', 'label' => 'Ergebnis']]">
                @foreach ($resolutions as $resolution)
                    <tr>
                        <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $resolution->id }}" aria-label="{{ $resolution->number() }} auswählen"></span></td>
                        <td>{{ $resolution->number() }}</td>
                        <td><a href="{{ route('board.resolutions.edit', $resolution) }}">{{ $resolution->title }}</a>@if ($resolution->votesLabel() !== '')<br><span class="vdbs-hint">{{ $resolution->votesLabel() }}</span>@endif</td>
                        <td>@if ($resolution->meeting)<a href="{{ route('board.meetings.show', $resolution->meeting) }}">{{ $resolution->meeting->starts_at->format('d.m.Y') }}</a>@else – @endif</td>
                        <td><x-vdbs.data.badge :tone="$tone[$resolution->outcome] ?? 'neutral'">{{ $resolution->outcomeLabel() }}</x-vdbs.data.badge></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="resolutions-form">Nichts ausgewählt.</p>
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Beschlüsse löschen? Verknüpfte Aufgaben bleiben erhalten.')">Auswahl löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>

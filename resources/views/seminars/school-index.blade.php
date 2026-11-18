@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen', 'href' => route('school.home')], ['label' => 'Seminare']];
@endphp

<x-layouts.app title="Seminare" width="wide">
    <x-vdbs.layout.page-header title="Seminare Ihrer Schule" lead="Fragen Sie ein Seminar an und sehen Sie, wie weit die Planung ist." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('school.seminars.create')" icon="plus">Seminar anfragen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Anfrage" icon="calendar"><p>Sobald Sie ein Seminar angefragt haben, sehen Sie es hier.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Seminare Ihrer Schule" :columns="[['key' => 'number', 'label' => 'Nummer'], ['key' => 'title', 'label' => 'Thema'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'status', 'label' => 'Stand']]">
            @foreach ($seminars as $seminar)
                <tr class="vdbs-table__row--link">
                    <td>{{ $seminar->number }}</td>
                    <td><a class="vdbs-table__row-link" href="{{ route('school.seminars.show', $seminar) }}">{{ $seminar->title }}</a></td>
                    <td>{{ $seminar->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>

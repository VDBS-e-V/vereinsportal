@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Meine Leitung']];
@endphp

<x-layouts.app title="Meine Leitung" width="wide">
    <x-vdbs.layout.page-header title="Seminare, die Sie leiten" lead="Hier bestätigen Sie die Durchführung und geben Bericht und Verträge ab." :breadcrumb="$crumbs" />

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Seminarleitung" icon="calendar"><p>Sobald die Koordination Sie als Seminarleitung bestimmt, erscheint das Seminar hier.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Seminare, die Sie leiten" :columns="[['key' => 'title', 'label' => 'Seminar'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'status', 'label' => 'Stand']]">
            @foreach ($seminars as $seminar)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('seminars.lead.show', $seminar) }}">{{ $seminar->title }}</a></td>
                    <td>{{ $seminar->partner->name }}</td>
                    <td>{{ $seminar->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>

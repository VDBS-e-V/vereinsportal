@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Meine Leitung']];
@endphp

<x-layouts.app title="Meine Leitung" width="wide">
    <x-vdbs.layout.page-header title="Meine Leitung" lead="Seminare, die Sie als Seminarleitung betreuen. Hier bestätigen Sie die Durchführung und laden Verträge und Ergebnisbericht hoch." :breadcrumb="$crumbs" />

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Seminarleitung" icon="clipboard-list"><p>Sie leiten zurzeit kein Seminar. Die Koordination bestimmt die Seminarleitung, wenn das Team bestätigt ist.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Seminare in meiner Leitung" :columns="[['key' => 'nr', 'label' => 'Nummer'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'title', 'label' => 'Seminar'], ['key' => 'status', 'label' => 'Status'], ['key' => 'due', 'label' => 'Unterlagen bis']]">
            @foreach ($seminars as $seminar)
                <tr>
                    <td>{{ $seminar->number }}</td>
                    <td>{{ $seminar->partner->name }}</td>
                    <td><a href="{{ route('seminar-lead.show', $seminar) }}">{{ $seminar->title }}</a></td>
                    <td><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></td>
                    <td>{{ $seminar->report_due_at?->format('d.m.Y') ?? '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>

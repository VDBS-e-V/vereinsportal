@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare']];
@endphp

<x-layouts.app title="Seminare" width="wide">
    <x-vdbs.layout.page-header title="Seminare planen" lead="Freigegebene Seminare: Termine festlegen, für das Team ausschreiben und die Auswahl treffen." :breadcrumb="$crumbs" />

    <x-vdbs.data.toolbar label="Seminare filtern" :search="false" :reset-href="route('seminars.plan.index')" :count="$seminars->count().' '.($seminars->count() === 1 ? 'Seminar' : 'Seminare')">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$status" />
        <x-vdbs.forms.select name="coordination" label="Koordination" placeholder="Alle" :options="$coordinationOptions" :value="$coordination" />
    </x-vdbs.data.toolbar>

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Seminare" icon="calendar"><p>Sobald die Geschäftsstelle ein Seminar freigibt, erscheint es hier.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Seminare" :columns="[['key' => 'number', 'label' => 'Nummer'], ['key' => 'title', 'label' => 'Thema'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'status', 'label' => 'Status']]">
            @foreach ($seminars as $seminar)
                <tr class="vdbs-table__row--link">
                    <td>{{ $seminar->number }}</td>
                    <td><a class="vdbs-table__row-link" href="{{ route('seminars.plan.show', $seminar) }}">{{ $seminar->title }}</a></td>
                    <td>{{ $seminar->partner->name }}</td>
                    <td>{{ $seminar->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>

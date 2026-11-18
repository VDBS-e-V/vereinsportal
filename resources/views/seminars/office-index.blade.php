@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seminare']];
    $count = $seminars->total().' '.($seminars->total() === 1 ? 'Seminar' : 'Seminare');
@endphp

<x-layouts.app title="Seminare" width="wide">
    <x-vdbs.layout.page-header title="Seminare" lead="Alle Seminare der Schulen mit Stand und Koordination." :breadcrumb="$crumbs" />

    <x-vdbs.data.toolbar label="Seminare filtern" :count="$count" :active="$active" :reset-href="route('seminars.office.index')" search-placeholder="Nummer, Thema oder Schule">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
        <x-vdbs.forms.select name="coordination" label="Koordination" placeholder="Alle" :options="$coordinationOptions" :value="$filters['coordination']" />
        <x-vdbs.forms.select name="school_year" label="Schuljahr" placeholder="Alle" :options="$schoolYears" :value="$filters['school_year']" />
    </x-vdbs.data.toolbar>

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Seminare gefunden" icon="calendar"><p>Zu den gewählten Filtern gibt es keine Seminare.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Seminare" :columns="[['key' => 'number', 'label' => 'Nummer'], ['key' => 'title', 'label' => 'Thema'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'coordination', 'label' => 'Koordination'], ['key' => 'status', 'label' => 'Status']]">
            @foreach ($seminars as $seminar)
                <tr class="vdbs-table__row--link">
                    <td>{{ $seminar->number }}</td>
                    <td><a class="vdbs-table__row-link" href="{{ route('seminars.office.show', $seminar) }}">{{ $seminar->title }}</a></td>
                    <td>{{ $seminar->partner->name }}</td>
                    <td>{{ $seminar->coordination?->name ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        {{ $seminars->links() }}
    @endif
</x-layouts.app>

@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Meine Seminare']];
@endphp

<x-layouts.app title="Meine Seminare" width="wide">
    <x-vdbs.layout.page-header title="Meine Seminare" lead="Freigegebene Seminare bis zum Abschluss, mit dem nächsten Schritt. Ihre Seminare sind die, bei denen Sie als verantwortliche Koordination eingetragen sind." :breadcrumb="$crumbs" />

    <p>
        @if ($all)
            <x-vdbs.actions.button :href="route('seminar-mine.index')" variant="secondary" icon="user">Nur meine Seminare</x-vdbs.actions.button>
        @else
            <x-vdbs.actions.button :href="route('seminar-mine.index', ['all' => 1])" variant="secondary" icon="users">Alle Seminare der Koordination</x-vdbs.actions.button>
        @endif
    </p>

    @if ($seminars->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Seminare" icon="calendar"><p>{{ $all ? 'Es gibt keine freigegebenen Seminare.' : 'Ihnen ist kein freigegebenes Seminar zugeordnet. Tragen Sie sich am Seminar als verantwortliche Koordination ein oder zeigen Sie alle Seminare.' }}</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Meine Seminare" :columns="[['key' => 'nr', 'label' => 'Nummer'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'title', 'label' => 'Seminar'], ['key' => 'date', 'label' => 'Nächster Termin'], ['key' => 'status', 'label' => 'Status'], ['key' => 'next', 'label' => 'Nächster Schritt']]">
            @foreach ($seminars as $seminar)
                @php $session = $seminar->sessions->sortBy('starts_at')->first(fn ($s) => $s->starts_at->isFuture()) ?? $seminar->sessions->sortBy('starts_at')->last(); @endphp
                <tr>
                    <td>{{ $seminar->number }}</td>
                    <td>{{ $seminar->partner->name }}</td>
                    <td><a href="{{ route('seminars.show', $seminar) }}">{{ $seminar->title }}</a></td>
                    <td>{{ $session?->period() ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></td>
                    <td>{{ $next[$seminar->status->value] ?? '' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>

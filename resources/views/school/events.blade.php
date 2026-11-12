@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen', 'href' => route('school.home')], ['label' => 'Veranstaltungen']];
@endphp

<x-layouts.app title="Veranstaltungen" width="wide">
    <x-vdbs.layout.page-header title="Veranstaltungen" lead="Melden Sie Schülerinnen und Schüler Ihrer Schule als Liste zu Großveranstaltungen an." :breadcrumb="$crumbs" />

    @if ($events->isEmpty())
        <x-vdbs.feedback.empty-state title="Zurzeit keine Veranstaltungen" icon="calendar"><p>Sobald eine Veranstaltung geplant ist, erscheint sie hier.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Veranstaltungen" :columns="[['key' => 'title', 'label' => 'Veranstaltung'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'status', 'label' => 'Anmeldung'], ['key' => 'own', 'label' => 'Von Ihrer Schule']]">
            @foreach ($events as $event)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('school.events.show', $event) }}">{{ $event->title }}</a></td>
                    <td>{{ $event->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-vdbs.data.badge></td>
                    <td>{{ $event->own_count }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>

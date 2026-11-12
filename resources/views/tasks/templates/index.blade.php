@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => $project->name, 'href' => route('tasks.projects.show', $project)], ['label' => 'Vorlagen']];
@endphp

<x-layouts.app title="Aufgabenvorlagen" width="wide">
    <x-vdbs.layout.page-header title="Vorlagen" :lead="'Wiederkehrende Aufgaben im Projekt „'.$project->name.'“: Eine Vorlage legt Titel, Beschreibung, Priorität, Fälligkeit, Labels und Unteraufgaben mit einem Klick an.'" :breadcrumb="$crumbs" />

    @if ($templates->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Vorlagen" icon="clipboard-list"><p>@if ($canManage)Legen Sie unten die erste Vorlage an oder speichern Sie eine bestehende Aufgabe als Vorlage.@else Die Verwaltenden des Projekts legen Vorlagen an.@endif</p></x-vdbs.feedback.empty-state>
    @else
        <form id="templates-form" method="post" action="{{ route('tasks.templates.destroy-many', $project) }}">
            @csrf
            <x-vdbs.data.table caption="Vorlagen" :columns="array_values(array_filter([$canManage ? ['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'templates-form'] : null, ['key' => 'name', 'label' => 'Vorlage'], ['key' => 'title', 'label' => 'Aufgabe'], ['key' => 'details', 'label' => 'Enthält']]))">
                @foreach ($templates as $template)
                    <tr>
                        @if ($canManage)<td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $template->id }}" aria-label="{{ $template->name }} auswählen"></span></td>@endif
                        <td>@if ($canManage)<a href="{{ route('tasks.templates.edit', [$project, $template]) }}">{{ $template->name }}</a>@else{{ $template->name }}@endif</td>
                        <td>{{ $template->title }}</td>
                        <td>
                            @foreach ($template->labels as $label)@include('tasks._label', ['label' => $label]) @endforeach
                            @if (count($template->subtaskTitles()) > 0)<span class="vdbs-hint">{{ count($template->subtaskTitles()) }} Unteraufgaben</span>@endif
                            @if ($template->due_in_days !== null)<span class="vdbs-hint">fällig nach {{ $template->due_in_days }} Tagen</span>@endif
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            @if ($canManage)
                <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="templates-form">Nichts ausgewählt.</p>
                <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Vorlagen löschen?')">Auswahl löschen</x-vdbs.actions.button>
            @endif
        </form>
    @endif

    @if ($canManage)
        <x-vdbs.layout.section title="Neue Vorlage">
            <x-vdbs.forms.form :action="route('tasks.templates.store', $project)" method="post">
                @include('tasks.templates._fields', ['template' => null])
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Vorlage anlegen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        </x-vdbs.layout.section>
    @endif
</x-layouts.app>

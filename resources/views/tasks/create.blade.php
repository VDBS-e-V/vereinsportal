@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.area', ['area' => $project->area])], ['label' => $project->name, 'href' => route('tasks.projects.show', $project)], ['label' => 'Neue Aufgabe']];
@endphp

<x-layouts.app title="Neue Aufgabe" width="medium">
    <x-vdbs.layout.page-header title="Neue Aufgabe" :lead="'Im Projekt '.$project->name.'.'" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('tasks.store', $project)" method="post" :summary="true">
        @include('tasks._fields', ['task' => null, 'resolutions' => [], 'statuses' => $statuses, 'showStatus' => true, 'labels' => $labels, 'defaultStatus' => $defaultStatus])
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="plus">Aufgabe anlegen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('tasks.projects.show', $project)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($templates !== [])
        <x-vdbs.layout.section title="Neue Aufgabe aus Vorlage">
            <x-vdbs.forms.form :action="route('tasks.templates.use', $project)" method="post" :summary="false">
                <x-vdbs.forms.select name="template" label="Vorlage" :required="true" placeholder="Vorlage wählen" :options="$templates" :value="old('template')" />
                <x-vdbs.forms.input name="title" label="Titel (leer = Titel der Vorlage)" :optional="true" maxlength="200" />
                <x-vdbs.forms.multi-select name="assignees" label="Zuständig" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$assignable" :value="[]" />
                <x-vdbs.forms.input name="due_on" label="Fällig am (leer = laut Vorlage)" type="date" :optional="true" />
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Aus Vorlage anlegen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        </x-vdbs.layout.section>
    @endif
</x-layouts.app>

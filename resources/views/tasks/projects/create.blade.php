@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => 'Neues Projekt']];
@endphp

<x-layouts.app title="Neues Projekt" width="wide">
    <x-vdbs.layout.page-header title="Neues Projekt" lead="Das Projekt beginnt mit den Spalten Offen, In Arbeit, Wartet und Erledigt. Die Spalten können Sie danach ändern." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('tasks.projects.store')" method="post">
        @include('tasks.projects._fields', ['project' => null, 'roleEditable' => true, 'managers' => [], 'members' => []])
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Projekt anlegen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('tasks.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

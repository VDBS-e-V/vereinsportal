@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => $project->name, 'href' => route('tasks.projects.show', $project)], ['label' => 'Vorlagen', 'href' => route('tasks.templates.index', $project)], ['label' => $template->name]];
@endphp

<x-layouts.app title="Vorlage bearbeiten" width="wide">
    <x-vdbs.layout.page-header :title="$template->name" lead="Vorlage bearbeiten. Schon angelegte Aufgaben ändern sich dadurch nicht." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('tasks.templates.update', [$project, $template])" method="put">
        @include('tasks.templates._fields', ['template' => $template])
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('tasks.templates.index', $project)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

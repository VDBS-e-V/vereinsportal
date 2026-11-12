@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Aufgaben', 'href' => route('board.tasks.index')], ['label' => 'Bearbeiten']];
@endphp

<x-layouts.app title="Aufgabe bearbeiten" width="wide">
    <x-vdbs.layout.page-header title="Aufgabe bearbeiten" :lead="$task->meeting ? 'Aus der Sitzung „'.$task->meeting->title.'“ am '.$task->meeting->starts_at->format('d.m.Y').'.' : ''" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('board.tasks.update', $task)" method="put">
        @include('board.tasks._fields', ['task' => $task])
        <x-vdbs.forms.checkbox name="done" label="Erledigt" value="1" :checked="old('done', $task->isDone())" />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('board.tasks.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

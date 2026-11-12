@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Beschlüsse', 'href' => route('board.resolutions.index')], ['label' => $resolution->number()]];
@endphp

<x-layouts.app :title="'Beschluss '.$resolution->number()" width="wide">
    <x-vdbs.layout.page-header :title="'Beschluss '.$resolution->number()" :lead="$resolution->meeting ? 'Gefasst in der Sitzung „'.$resolution->meeting->title.'“ am '.$resolution->meeting->starts_at->format('d.m.Y').'.' : ''" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('board.resolutions.update', $resolution)" method="put">
        @include('board.resolutions._fields', ['resolution' => $resolution])
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="$resolution->meeting ? route('board.meetings.show', $resolution->meeting) : route('board.resolutions.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

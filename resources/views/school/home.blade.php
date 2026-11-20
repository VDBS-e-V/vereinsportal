@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen']];
@endphp

<x-layouts.app :title="$account->partner->name" width="wide">
    <x-vdbs.layout.page-header :title="$account->partner->name" lead="Ihr Bereich beim VDBS." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('school.seminars.index')" icon="calendar">Seminare</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.feedback.empty-state title="Willkommen" icon="school">
        <p>Hier fragen Sie Seminare an und sehen den Stand Ihrer Anfragen.</p>
    </x-vdbs.feedback.empty-state>
</x-layouts.app>

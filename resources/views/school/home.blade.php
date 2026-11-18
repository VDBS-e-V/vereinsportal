@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen']];
@endphp

<x-layouts.app :title="$account->partner->name" width="wide">
    <x-vdbs.layout.page-header :title="$account->partner->name" lead="Ihr Bereich beim VDBS." :breadcrumb="$crumbs">
    </x-vdbs.layout.page-header>

    <x-vdbs.feedback.empty-state title="Willkommen" icon="school">
        <p>Ihr Bereich beim VDBS wird zurzeit neu aufgebaut.</p>
    </x-vdbs.feedback.empty-state>
</x-layouts.app>

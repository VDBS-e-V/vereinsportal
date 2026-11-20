@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Bildungsreferenten'], ['label' => 'Übersicht']];
@endphp

<x-layouts.app title="Bildungsreferenten" width="wide">
    <x-vdbs.layout.page-header title="Übersicht für Bildungsreferenten" lead="Ihre Aufgaben im Überblick." :breadcrumb="$crumbs" />

    @include('portal._tasks', ['area' => \App\Support\PortalAreas::Bildungsreferenten])
</x-layouts.app>

@props(['current'])

{{--
    Umschalter der Verwaltung: Nutzer | Personen, je nach Berechtigung. Gehört in den Slot "tabs" des Seitenkopfs.
    current: users | people | audit
--}}

@php
    $items = array_values(array_filter([
        auth()->user()?->can('users.view') ? ['label' => 'Nutzer', 'href' => route('admin.users.index'), 'icon' => 'users', 'current' => $current === 'users'] : null,
        auth()->user()?->can('people.view') ? ['label' => 'Personen', 'href' => route('admin.people.index'), 'icon' => 'user', 'current' => $current === 'people'] : null,
        auth()->user()?->can('audit.view') ? ['label' => 'Protokoll', 'href' => route('admin.audit.index'), 'icon' => 'clock', 'current' => $current === 'audit'] : null,
    ]));
@endphp

@if (count($items) > 1)
    <x-vdbs.navigation.tab-nav label="Verwaltung" :items="$items" />
@endif

@props(['current'])

{{--
    Umschalter der Verwaltung: Nutzer | Personen, je nach Berechtigung. Gehört in den Slot "tabs" des Seitenkopfs.
    current: users | people
--}}

@php
    $items = array_values(array_filter([
        auth()->user()?->can('users.view') ? ['label' => 'Nutzer', 'href' => route('admin.users.index'), 'icon' => 'users', 'current' => $current === 'users'] : null,
        auth()->user()?->can('people.view') ? ['label' => 'Personen', 'href' => route('admin.people.index'), 'icon' => 'user', 'current' => $current === 'people'] : null,
    ]));
@endphp

@if (count($items) > 1)
    <x-vdbs.navigation.tab-nav label="Verwaltung" :items="$items" />
@endif

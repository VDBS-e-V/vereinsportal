@props(['title', 'current', 'lead' => null, 'width' => 'medium'])

{{--
    Rahmen der Konto-Seiten: Seitenkopf mit Brotkrumen und der Tab-Leiste Profil | E-Mail | Passwort | Sicherheit | Sitzungen.
    current: profile | email | password | security | sessions
    Slots: Standard (Inhalt), meta (z. B. Badge neben dem Titel)
--}}

@php
    $tabs = [
        ['key' => 'profile', 'label' => 'Profil', 'href' => route('account.profile'), 'icon' => 'user'],
        ['key' => 'email', 'label' => 'E-Mail', 'href' => route('account.email'), 'icon' => 'mail'],
        ['key' => 'password', 'label' => 'Passwort', 'href' => route('account.password'), 'icon' => 'key-round'],
        ['key' => 'security', 'label' => 'Sicherheit', 'href' => route('account.security.index'), 'icon' => 'shield'],
        ['key' => 'sessions', 'label' => 'Sitzungen', 'href' => route('account.sessions'), 'icon' => 'clock'],
    ];
    $items = array_map(fn (array $tab): array => ['label' => $tab['label'], 'href' => $tab['href'], 'icon' => $tab['icon'], 'current' => $tab['key'] === $current], $tabs);
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto'], ['label' => $title]];
@endphp

<x-layouts.app :title="$title" :width="$width">
    <x-vdbs.layout.page-header :title="$title" :lead="$lead" :breadcrumb="$crumbs">
        @isset($meta)
            <x-slot:meta>{{ $meta }}</x-slot:meta>
        @endisset
        <x-slot:tabs>
            <x-vdbs.navigation.tab-nav label="Konto" :items="$items" />
        </x-slot:tabs>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    {{ $slot }}
</x-layouts.app>

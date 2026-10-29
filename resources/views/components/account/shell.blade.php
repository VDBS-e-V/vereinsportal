@props(['title', 'current', 'lead' => null, 'width' => 'medium'])

{{--
    Rahmen der Konto-Seiten: Seitenkopf mit Brotkrumen. Die Seiten des Bereichs Konto (Profil, E-Mail, Passwort, Sicherheit, Sitzungen) stehen in Zeile 2 des Kopfbereichs.
    current: profile | email | password | security | sessions
    Slots: Standard (Inhalt), meta (z. B. Badge neben dem Titel)
--}}

@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto'], ['label' => $title]];
@endphp

<x-layouts.app :title="$title" :width="$width">
    <x-vdbs.layout.page-header :title="$title" :lead="$lead" :breadcrumb="$crumbs">
        @isset($meta)
            <x-slot:meta>{{ $meta }}</x-slot:meta>
        @endisset
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    {{ $slot }}
</x-layouts.app>

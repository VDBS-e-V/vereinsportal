@php
    $home = config('portal.home');
    $contactLines = $home['contact']['lines'] ?? [];
    $areas = \App\Support\PortalAreas::withGroupMark();
@endphp

<x-layouts.app title="Start" width="full">
    {{-- Einstieg: Titel, Einleitung und die wichtigste Aktion je nach Anmeldestatus --}}
    <x-vdbs.media.hero variant="compact" :bleed="true" :title="$home['title']" :lead="$home['lead']">
        <x-slot:actions>
            @auth
                <x-vdbs.actions.button href="#" icon="user">Mein Konto</x-vdbs.actions.button>
            @else
                <x-vdbs.actions.button :href="route('login')" icon="log-in">Anmelden</x-vdbs.actions.button>
                <x-vdbs.actions.button href="#" variant="secondary">Über das Portal</x-vdbs.actions.button>
            @endauth
        </x-slot:actions>
    </x-vdbs.media.hero>

    <x-vdbs.layout.container>
        <x-vdbs.cards.quick-links label="Schnellzugriff">
            <x-vdbs.cards.quick-link label="Über das Portal" href="#" :tone="4" icon="info" />
            @auth
                <x-vdbs.cards.quick-link label="Mein Konto" href="#" :tone="3" icon="user" />
            @else
                <x-vdbs.cards.quick-link label="Anmelden" :href="route('login')" :tone="1" icon="log-in" />
            @endauth
            <x-vdbs.cards.quick-link label="Hilfe" href="#" :tone="6" icon="circle-help" />
            <x-vdbs.cards.quick-link label="Kontakt" href="#" :tone="2" icon="mail" />
        </x-vdbs.cards.quick-links>
    </x-vdbs.layout.container>

    <x-vdbs.layout.section :title="$home['areas']['title']" :lead="$home['areas']['lead']" align="center">
        <x-vdbs.data.chip-list label="Bereiche des Portals">
            @foreach ($areas as $area)
                <x-vdbs.data.chip :label="$area->label()" href="#" :icon="$area->icon()" :mark="$area->groupMark()" />
            @endforeach
        </x-vdbs.data.chip-list>
    </x-vdbs.layout.section>

    <x-vdbs.layout.section variant="band">
        <x-vdbs.layout.split :title="$home['access']['title']">
            <p>{{ auth()->check() ? $home['access']['member'] : $home['access']['guest'] }}</p>

            <x-slot:actions>
                @auth
                    <x-vdbs.actions.link-button href="#">Mein Konto</x-vdbs.actions.link-button>
                @else
                    <x-vdbs.actions.link-button :href="route('login')">Zur Anmeldung</x-vdbs.actions.link-button>
                @endauth
                <x-vdbs.actions.link-button href="#" variant="all">Hilfe kontaktieren</x-vdbs.actions.link-button>
            </x-slot:actions>
        </x-vdbs.layout.split>
    </x-vdbs.layout.section>

    @if ($contactLines !== [])
        <x-vdbs.layout.section>
            <x-vdbs.cards.contact-block :title="$home['contact']['title']" :text="$home['contact']['text']" :lines="$contactLines" />
        </x-vdbs.layout.section>
    @endif

    <x-vdbs.actions.back-to-top />
</x-layouts.app>

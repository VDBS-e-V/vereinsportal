@php
    $home = config('portal.home');
    $areas = \App\Support\PortalAreas::withGroupMark();
    // Angemeldete gelangen über die Chips zur ersten Seite ihres Bereichs; Gäste sehen die Chips nur als Überblick
    $visibleAreas = auth()->check() ? \App\Support\PortalNavigation::visibleAreas(auth()->user()) : [];
    $areaLink = fn (\App\Support\PortalAreas $area): string => in_array($area, $visibleAreas, true) ? (\App\Support\PortalNavigation::pages($area, auth()->user())[0]['href'] ?? '#') : '#';
@endphp

<x-layouts.app title="Start" width="full">
    {{-- Einstieg: Bild mit mittigem Titel, darunter die Schnellzugriffe als farbige Kacheln --}}
    <x-vdbs.layout.container style="padding-top: var(--space-8)">
        <x-vdbs.media.hero variant="image" align="center" :title="$home['title']" :image="asset($home['hero_image']['src'])" :image-alt="$home['hero_image']['alt']" :credit="$home['hero_image']['credit']">
            <x-slot:footer>
                <x-vdbs.cards.quick-links label="Schnellzugriff" variant="solid">
                    <x-vdbs.cards.quick-link label="Über das Portal" :href="route('portal.about')" :tone="3" />
                    <x-vdbs.cards.quick-link label="Zugang zum Portal" :href="route('portal.access')" :tone="5" />
                    @auth
                        <x-vdbs.cards.quick-link label="Mein Konto" :href="route('account.home')" :tone="1" />
                    @else
                        <x-vdbs.cards.quick-link label="Anmelden" :href="route('login')" :tone="1" />
                    @endauth
                </x-vdbs.cards.quick-links>
            </x-slot:footer>
        </x-vdbs.media.hero>
    </x-vdbs.layout.container>

    <x-vdbs.layout.section :title="$home['areas']['title']" :lead="$home['areas']['lead']" align="center" variant="band">
        <x-vdbs.data.chip-list label="Bereiche des Portals" spread>
            @foreach ($areas as $area)
                <x-vdbs.data.chip :label="$area->label()" :href="$areaLink($area)" :group="$area->groupMark()" />
            @endforeach
        </x-vdbs.data.chip-list>
    </x-vdbs.layout.section>

    {{-- Zugang: Text mit Aktionen links, Bild rechts (Bild und Quelle aus config portal.home.access_image); grauer Streifen im Wechsel mit weißen Abschnitten --}}
    <x-vdbs.layout.section variant="band">
        <x-vdbs.layout.split :title="$home['access']['title']">
            <p>{{ auth()->check() ? $home['access']['member'] : $home['access']['guest'] }}</p>

            <x-slot:actions>
                @auth
                    <x-vdbs.actions.button :href="route('account.home')" variant="primary" icon="arrow-right" icon-position="end">Mein Konto</x-vdbs.actions.button>
                @else
                    <x-vdbs.actions.button :href="route('portal.access')" variant="primary" icon="arrow-right" icon-position="end">Zugang zum Portal</x-vdbs.actions.button>
                @endauth
            </x-slot:actions>

            @if (filled($home['access_image']['src'] ?? null))
                <x-slot:media>
                    <x-vdbs.media.figure :src="asset($home['access_image']['src'])" :alt="$home['access_image']['alt']" :credit="$home['access_image']['credit']" credit-placement="edge" ratio="3-2" />
                </x-slot:media>
            @endif
        </x-vdbs.layout.split>
    </x-vdbs.layout.section>

    <x-vdbs.actions.back-to-top />
</x-layouts.app>

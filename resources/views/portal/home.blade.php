@php
    $home = config('portal.home');
    $contactLines = $home['contact']['lines'] ?? [];
    $areas = \App\Support\PortalAreas::withGroupMark();
@endphp

<x-layouts.app title="Start" width="full">
    {{-- Einstieg: Bild mit mittigem Titel, darunter die Schnellzugriffe als farbige Kacheln --}}
    <x-vdbs.layout.container>
        <x-vdbs.media.hero variant="image" align="center" :title="$home['title']" :image="asset($home['hero_image']['src'])" :image-alt="$home['hero_image']['alt']" :credit="$home['hero_image']['credit']">
            <x-slot:footer>
                <x-vdbs.cards.quick-links label="Schnellzugriff" variant="solid">
                    <x-vdbs.cards.quick-link label="Über das Portal" :href="route('portal.about')" :tone="3" />
                    <x-vdbs.cards.quick-link label="Zugang zum Portal" :href="route('portal.access')" :tone="5" />
                    @auth
                        <x-vdbs.cards.quick-link label="Mein Konto" :href="route('account.profile')" :tone="1" />
                    @else
                        <x-vdbs.cards.quick-link label="Anmelden" :href="route('login')" :tone="1" />
                    @endauth
                    <x-vdbs.cards.quick-link label="Hilfe" :href="route('portal.about').'#portal-faq'" :tone="6" />
                </x-vdbs.cards.quick-links>
            </x-slot:footer>
        </x-vdbs.media.hero>
    </x-vdbs.layout.container>

    <x-vdbs.layout.section :title="$home['areas']['title']" :lead="$home['areas']['lead']" align="center">
        <x-vdbs.data.chip-list label="Bereiche des Portals">
            @foreach ($areas as $area)
                <x-vdbs.data.chip :label="$area->label()" href="#" :group="$area->groupMark()" />
            @endforeach
        </x-vdbs.data.chip-list>
    </x-vdbs.layout.section>

    @if ($articles->isNotEmpty())
        <x-vdbs.layout.section title="Empfohlene Artikel" lead="Lesen Sie unsere aktuellen Themenartikel." align="center" id="artikel">
            <x-vdbs.layout.grid :columns="3" align="start">
                @foreach ($articles as $article)
                    <x-vdbs.cards.teaser
                        :title="$article->title"
                        :href="route('articles.public.show', $article->slug)"
                        :text="$article->excerpt()"
                        :image="filled($article->image_path) && filled($article->image_credit) ? route('articles.public.image', $article->slug) : null"
                        :imageAlt="$article->image_alt ?? ''"
                        :credit="$article->image_credit"
                        :category="$article->category" />
                @endforeach
            </x-vdbs.layout.grid>

            <x-slot:actions>
                <x-vdbs.actions.link-button :href="route('articles.public.index')" variant="all">Alle Artikel anzeigen</x-vdbs.actions.link-button>
            </x-slot:actions>
        </x-vdbs.layout.section>
    @endif

    {{-- Zugang: Text mit Aktionen links, Bild rechts (Bild und Quelle aus config portal.home.access_image) --}}
    <x-vdbs.layout.section>
        <x-vdbs.layout.split :title="$home['access']['title']">
            <p>{{ auth()->check() ? $home['access']['member'] : $home['access']['guest'] }}</p>

            <x-slot:actions>
                @auth
                    <x-vdbs.actions.link-button :href="route('account.profile')">Mein Konto</x-vdbs.actions.link-button>
                @else
                    <x-vdbs.actions.link-button :href="route('portal.access')">Zugang zum Portal</x-vdbs.actions.link-button>
                @endauth
                <x-vdbs.actions.link-button :href="route('portal.contact')" variant="all">Hilfe kontaktieren</x-vdbs.actions.link-button>
            </x-slot:actions>

            @if (filled($home['access_image']['src'] ?? null))
                <x-slot:media>
                    <x-vdbs.media.figure :src="asset($home['access_image']['src'])" :alt="$home['access_image']['alt']" :credit="$home['access_image']['credit']" credit-placement="edge" ratio="3-2" />
                </x-slot:media>
            @endif
        </x-vdbs.layout.split>
    </x-vdbs.layout.section>

    {{-- Kontakt: graue Fläche, Bild links, Text mit E-Mail-Adressen rechts --}}
    <x-vdbs.layout.section>
        @if ($contactLines !== [])
            <x-vdbs.cards.contact-block :surface="true" :title="$home['contact']['title']" :text="$home['contact']['text']" :lines="$contactLines">
                @if (filled($home['contact']['image']['src'] ?? null))
                    <x-slot:media>
                        <x-vdbs.media.figure :src="asset($home['contact']['image']['src'])" :alt="$home['contact']['image']['alt']" :credit="$home['contact']['image']['credit']" credit-placement="edge" ratio="3-2" />
                    </x-slot:media>
                @endif
                <x-slot:actions><x-vdbs.actions.link-button :href="route('portal.contact')">Zum Kontaktformular</x-vdbs.actions.link-button></x-slot:actions>
            </x-vdbs.cards.contact-block>
        @else
            <x-vdbs.layout.split :title="$home['contact']['title']">
                <p>{{ $home['contact']['text'] }}</p>

                <x-slot:actions>
                    <x-vdbs.actions.link-button :href="route('portal.contact')">Zum Kontaktformular</x-vdbs.actions.link-button>
                </x-slot:actions>
            </x-vdbs.layout.split>
        @endif
    </x-vdbs.layout.section>

    <x-vdbs.actions.back-to-top />
</x-layouts.app>

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
                <x-vdbs.actions.button :href="route('account.profile')" icon="user">Mein Konto</x-vdbs.actions.button>
            @else
                <x-vdbs.actions.button :href="route('login')" icon="log-in">Anmelden</x-vdbs.actions.button>
                <x-vdbs.actions.button :href="route('portal.access')" variant="secondary">Zugang beantragen</x-vdbs.actions.button>
                <x-vdbs.actions.button :href="route('join.apply')" variant="tertiary">Mitglied werden</x-vdbs.actions.button>
            @endauth
        </x-slot:actions>
    </x-vdbs.media.hero>

    <x-vdbs.layout.container>
        <x-vdbs.cards.quick-links label="Schnellzugriff">
            <x-vdbs.cards.quick-link label="Über das Portal" :href="route('portal.about')" :tone="4" icon="info" />
            <x-vdbs.cards.quick-link label="Zugang zum Portal" :href="route('portal.access')" :tone="1" icon="log-in" />
            @auth
                <x-vdbs.cards.quick-link label="Mein Konto" :href="route('account.profile')" :tone="3" icon="user" />
            @else
                <x-vdbs.cards.quick-link label="Anmelden" :href="route('login')" :tone="3" icon="user" />
            @endauth
            <x-vdbs.cards.quick-link label="Hilfe" :href="route('portal.about').'#portal-faq'" :tone="6" icon="circle-help" />
        </x-vdbs.cards.quick-links>
    </x-vdbs.layout.container>

    <x-vdbs.layout.section :title="$home['areas']['title']" :lead="$home['areas']['lead']" align="center">
        <x-vdbs.data.chip-list label="Bereiche des Portals">
            @foreach ($areas as $area)
                <x-vdbs.data.chip :label="$area->label()" href="#" :group="$area->groupMark()" />
            @endforeach
        </x-vdbs.data.chip-list>
    </x-vdbs.layout.section>

    @if ($articles->isNotEmpty())
        <x-vdbs.layout.section title="Empfohlene Artikel" lead="Aktuelle Themenartikel" align="center" id="artikel">
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

    <x-vdbs.layout.section>
        @if ($contactLines !== [])
            <x-vdbs.cards.contact-block :title="$home['contact']['title']" :text="$home['contact']['text']" :lines="$contactLines">
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

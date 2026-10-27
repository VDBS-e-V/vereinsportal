<p>Zusammenspiel der Komponenten für die Startseite (Platzhalterinhalte).</p>

<x-vdbs.media.hero variant="image" title="Willkommen im VDBS Serviceportal" image="/images/placeholder/hero.svg" image-alt="" align="center">
    <x-slot:footer>
        <x-vdbs.cards.quick-links>
            <x-vdbs.cards.quick-link label="Über das Portal" href="#" :tone="4" />
            <x-vdbs.cards.quick-link label="Zugang zum Portal" href="#" :tone="1" />
            <x-vdbs.cards.quick-link label="Mein Konto" href="#" :tone="3" />
            <x-vdbs.cards.quick-link label="Hilfe" href="#" :tone="6" />
        </x-vdbs.cards.quick-links>
    </x-slot:footer>
</x-vdbs.media.hero>

<x-vdbs.layout.section title="Das VDBS Serviceportal" lead="Hier finden Sie alle Bereiche." align="center">
    <x-vdbs.data.chip-list label="Bereiche">
        @foreach (\App\Support\PortalAreas::withGroupMark() as $area)
            <x-vdbs.data.chip :label="$area->label()" href="#" :icon="$area->icon()" />
        @endforeach
    </x-vdbs.data.chip-list>
</x-vdbs.layout.section>

<x-vdbs.layout.section title="Empfohlene Artikel" lead="Aktuelle Themenartikel" align="center">
    <x-vdbs.layout.grid :columns="3">
        <x-vdbs.cards.teaser title="Mehr Sicherheit" href="#" text="Anreißer." image="/images/placeholder/tile.svg" category="Themenartikel" />
        <x-vdbs.cards.teaser title="Digitalisierte Verwaltung" href="#" text="Anreißer." image="/images/placeholder/tile.svg" category="Themenartikel" />
        <x-vdbs.cards.teaser title="Eigene Cloud" href="#" text="Anreißer." category="Themenartikel" />
    </x-vdbs.layout.grid>
    <x-slot:actions><x-vdbs.actions.link-button href="#" variant="all">Alle Artikel anzeigen</x-vdbs.actions.link-button></x-slot:actions>
</x-vdbs.layout.section>

<x-vdbs.layout.section>
    <x-vdbs.layout.split title="Zugang zum Portal">
        <p>Hier finden Sie alle Informationen, um auf das Portal zuzugreifen.</p>
        <x-slot:actions><x-vdbs.actions.link-button href="#">Zugang zum Portal</x-vdbs.actions.link-button></x-slot:actions>
        <x-slot:media><x-vdbs.media.figure src="/images/placeholder/hero.svg" alt="" /></x-slot:media>
    </x-vdbs.layout.split>
</x-vdbs.layout.section>

<x-vdbs.layout.section variant="band">
    <x-vdbs.cards.contact-block :lines="[['label' => 'Support', 'value' => 'support@example.org', 'href' => 'mailto:support@example.org']]">
        <x-slot:media><x-vdbs.media.figure src="/images/placeholder/hero.svg" alt="" /></x-slot:media>
        <x-slot:actions><x-vdbs.actions.link-button href="#">Zum Kontaktformular</x-vdbs.actions.link-button></x-slot:actions>
    </x-vdbs.cards.contact-block>
</x-vdbs.layout.section>

<x-vdbs.actions.back-to-top />

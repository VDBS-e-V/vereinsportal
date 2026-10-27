{{-- Läuft in voller Seitenbreite (Registry: width = full): Abschnitte und Hero bringen ihre Container selbst mit. --}}

<x-vdbs.media.hero
    variant="overlay"
    size="l"
    :bleed="true"
    title="Willkommen im VDBS Serviceportal"
    lead="Die zentrale Anlaufstelle für alle Informationen und Dienstleistungen rund um den VDBS."
    :image="placeholder('hero', 1)"
    image-alt=""
>
    <x-slot:actions>
        <x-vdbs.actions.link-button href="#">Zugang zum Portal</x-vdbs.actions.link-button>
        <x-vdbs.actions.link-button href="#" variant="line">Über das Portal</x-vdbs.actions.link-button>
    </x-slot:actions>
</x-vdbs.media.hero>

<x-vdbs.cards.quick-links>
    <x-vdbs.cards.quick-link label="Über das Portal" href="#" :tone="4" />
    <x-vdbs.cards.quick-link label="Zugang zum Portal" href="#" :tone="1" />
    <x-vdbs.cards.quick-link label="Mein Konto" href="#" :tone="3" icon="user" />
    <x-vdbs.cards.quick-link label="Hilfe" href="#" :tone="6" icon="info" />
</x-vdbs.cards.quick-links>

<x-vdbs.layout.section title="Das VDBS Serviceportal" lead="Hier finden Sie alle Bereiche des Portals." align="center">
    <x-vdbs.data.chip-list label="Bereiche">
        @foreach (\App\Support\PortalAreas::withGroupMark() as $area)
            <x-vdbs.data.chip :label="$area->label()" href="#" :icon="$area->icon()" />
        @endforeach
    </x-vdbs.data.chip-list>
</x-vdbs.layout.section>

<x-vdbs.layout.section title="Empfohlene Artikel" lead="Aktuelle Themenartikel" variant="band" align="center">
    <x-vdbs.layout.grid :columns="3">
        <x-vdbs.cards.teaser title="Mehr Sicherheit" href="#" text="Kurzer Anreißer des Artikels." :image="placeholder('teaser', 1)" category="Themenartikel" />
        <x-vdbs.cards.teaser title="Digitalisierte Verwaltung" href="#" text="Kurzer Anreißer des Artikels." :image="placeholder('teaser', 2)" category="Themenartikel" />
        <x-vdbs.cards.teaser title="Eigene Cloud" href="#" text="Kurzer Anreißer des Artikels." :image="placeholder('teaser', 3)" category="Themenartikel" />
    </x-vdbs.layout.grid>
    <x-slot:actions><x-vdbs.actions.link-button href="#" variant="all">Alle Artikel anzeigen</x-vdbs.actions.link-button></x-slot:actions>
</x-vdbs.layout.section>

<x-vdbs.layout.section>
    <x-vdbs.layout.split title="Zugang zum Portal">
        <p>Hier finden Sie alle Informationen, die Sie benötigen, um auf das Portal zuzugreifen. Mit Konto können Sie sich anmelden, sonst können Sie sich registrieren.</p>
        <x-slot:actions>
            <x-vdbs.actions.link-button href="#">Zugang zum Portal</x-vdbs.actions.link-button>
            <x-vdbs.actions.link-button href="#" variant="all">Hilfe kontaktieren</x-vdbs.actions.link-button>
        </x-slot:actions>
        <x-slot:media><x-vdbs.media.figure :src="placeholder('split', 1)" alt="" /></x-slot:media>
    </x-vdbs.layout.split>
</x-vdbs.layout.section>

<x-vdbs.layout.section variant="band">
    <x-vdbs.cards.contact-block text="Sollten Sie Fragen oder Probleme haben, können Sie uns jederzeit kontaktieren." :lines="[
        ['label' => 'Support', 'value' => 'support@example.org', 'href' => 'mailto:support@example.org'],
        ['label' => 'Kontakt', 'value' => 'kontakt@example.org', 'href' => 'mailto:kontakt@example.org'],
    ]">
        <x-slot:media><x-vdbs.media.figure :src="placeholder('contact', 1)" alt="" /></x-slot:media>
        <x-slot:actions><x-vdbs.actions.link-button href="#">Zum Kontaktformular</x-vdbs.actions.link-button></x-slot:actions>
    </x-vdbs.cards.contact-block>
</x-vdbs.layout.section>

<x-vdbs.actions.back-to-top />

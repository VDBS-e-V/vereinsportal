<p>Paket <strong>Karten</strong> · <code>&lt;x-vdbs.cards.teaser&gt;</code> · Vorbild: FU Berlin Nachrichten und Standorte</p>

<h2>default · Karte mit Schatten</h2>
<x-vdbs.layout.grid :columns="3">
    <x-vdbs.cards.teaser title="Mehr Sicherheit" href="#" text="Kurzer Anreißer des Artikels." :image="placeholder('teaser', 1)" category="Themenartikel" />
    <x-vdbs.cards.teaser title="Digitalisierte Verwaltung" href="#" text="Kurzer Anreißer des Artikels." :image="placeholder('teaser', 2)" category="Themenartikel" />
    <x-vdbs.cards.teaser title="Eigene Cloud" href="#" text="Ohne Bild, nur Text." category="Themenartikel" />
</x-vdbs.layout.grid>

<h2>flat · Nachrichten, Karten mit eigener Höhe (align="start")</h2>
<x-vdbs.layout.grid :columns="3" align="start">
    <x-vdbs.cards.teaser variant="flat" title="Statement zu den Vorfällen" href="#" :image="placeholder('teaser', 1)" ratio="16-9" />
    <x-vdbs.cards.teaser variant="flat" title="Science Week | Von „Berliner Schnauze“ bis zu „fiesen Pflanzen“" href="#" text="1. bis 9. November: Vorträge, Diskussionen und Führungen von Forschenden." :image="placeholder('teaser', 2)" ratio="4-3" />
    <x-vdbs.cards.teaser variant="flat" title="Interview | „Wie wir miteinander an Lösungen arbeiten, hat mir gezeigt, wie besonders das Portal für mich ist“" href="#" text="Ein Gespräch zum Amtsantritt der neuen Leitung." :image="placeholder('teaser', 3)" ratio="original" />
</x-vdbs.layout.grid>

<h2>featured · Aufmacher</h2>
<x-vdbs.cards.teaser variant="featured" title="Statement der Hochschulleitung zu queerfeindlichen Vorfällen" href="#" :image="placeholder('hero', 1)" />

<h2>compact auf grauem Band (tone="white")</h2>
<x-vdbs.layout.section variant="band" title="Standorte">
    <x-vdbs.layout.grid :columns="4" align="start">
        <x-vdbs.cards.teaser variant="compact" tone="white" title="Zentralbibliothek" meta="Garystr. 39, 14195 Berlin" href="#" :image="placeholder('tile', 1)" />
        <x-vdbs.cards.teaser variant="compact" tone="white" title="Campusbibliothek" meta="Fabeckstr. 23/25, 14195 Berlin" href="#" :image="placeholder('tile', 2)" />
        <x-vdbs.cards.teaser variant="compact" tone="white" title="Philologische Bibliothek" meta="Habelschwerdter Allee 45, 14195 Berlin" href="#" :image="placeholder('tile', 3)" />
        <x-vdbs.cards.teaser variant="compact" tone="white" title="Fachbibliothek Rechtswissenschaft" meta="Hoffstraße 8, 14195 Berlin" href="#" :image="placeholder('tile', 4)" />
    </x-vdbs.layout.grid>
</x-vdbs.layout.section>

<p>Paket <strong>Medien</strong> · <code>&lt;x-vdbs.media.hero&gt;</code> und <code>&lt;x-vdbs.media.hero-tile&gt;</code></p>

<h2>Variante „image“</h2>
<x-vdbs.media.hero
    variant="image"
    title="Willkommen im VDBS Serviceportal"
    lead="Die zentrale Anlaufstelle für alle Informationen und Dienstleistungen rund um den VDBS."
    :image="placeholder('hero', 1)"
    image-alt=""
    credit="Bild: Platzhalter"
    align="center"
/>

<h2>Variante „caption“</h2>
<x-vdbs.media.hero
    variant="caption"
    title="Statement des Vorstands"
    :image="placeholder('hero', 2)"
    image-alt="Platzhalterbild mit Kreis und Linien"
    credit="Bild: Platzhalter"
    caption="Statement des Vorstands zur aktuellen Lage"
    caption-href="#"
    caption-label="Statement lesen"
/>

<h2>Variante „panel“</h2>
<x-vdbs.media.hero variant="panel" title="Ich brauche Informationen zu:" :level="2">
    <x-slot:search>
        <x-vdbs.forms.input name="hero_search" label="Suchbegriff" type="search" />
    </x-slot:search>
    <x-slot:aside>
        <x-vdbs.media.hero-tile title="Teamende" href="#" :image="placeholder('tile', 1)" credit="Bild: Platzhalter" />
        <x-vdbs.media.hero-tile title="Schulen" href="#" :image="placeholder('tile', 2)" />
        <x-vdbs.media.hero-tile title="Mitglieder" href="#" :image="placeholder('tile', 3)" />
        <x-vdbs.media.hero-tile title="Hilfe" href="#" :image="placeholder('tile', 4)" />
    </x-slot:aside>
</x-vdbs.media.hero>

<h2>Eigenschaften</h2>
<ul>
    <li><code>title</code> ist Pflicht; <code>level</code> (1–3) bestimmt die Überschriftenebene, pro Seite genau ein h1.</li>
    <li><code>image-alt=""</code> markiert das Bild als dekorativ, sonst eine Beschreibung angeben.</li>
    <li>Der Bildnachweis (<code>credit</code>) steht als sichtbarer Text im Bild.</li>
    <li>Slots: <code>search</code>, <code>actions</code>, <code>aside</code>, <code>footer</code>.</li>
</ul>

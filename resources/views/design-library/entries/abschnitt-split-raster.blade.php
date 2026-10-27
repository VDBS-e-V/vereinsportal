<p>Paket <strong>Layout</strong> · <code>&lt;x-vdbs.layout.section&gt;</code>, <code>&lt;x-vdbs.layout.split&gt;</code>, <code>&lt;x-vdbs.layout.grid&gt;</code></p>

<h2>Abschnitt</h2>
<x-vdbs.layout.section title="Das VDBS Serviceportal" lead="Die zentrale Anlaufstelle für alle Informationen." align="center">
    <p>Inhalt des Abschnitts.</p>
    <x-slot:actions><x-vdbs.actions.link-button href="#">Mehr erfahren</x-vdbs.actions.link-button></x-slot:actions>
</x-vdbs.layout.section>

<h2>Abschnitt als Band</h2>
<x-vdbs.layout.section title="Haltung und Verantwortung" variant="band">
    <p>Text auf grauer Fläche.</p>
</x-vdbs.layout.section>

<h2>Split</h2>
<x-vdbs.layout.split title="Zugang zum Portal">
    <p>Text neben dem Bild.</p>
    <x-slot:actions><x-vdbs.actions.button>Zum Portal</x-vdbs.actions.button></x-slot:actions>
    <x-slot:media><x-vdbs.media.figure src="/images/placeholder/hero.svg" alt="" credit="Bild: Platzhalter" /></x-slot:media>
</x-vdbs.layout.split>

<h2>Split umgekehrt</h2>
<x-vdbs.layout.split title="Forschung" :reverse="true">
    <p>Bild links, Text rechts.</p>
    <x-slot:media><x-vdbs.media.figure src="/images/placeholder/hero.svg" alt="" /></x-slot:media>
</x-vdbs.layout.split>

<h2>Raster</h2>
<x-vdbs.layout.grid :columns="3">
    <x-vdbs.cards.card title="Eins">Inhalt</x-vdbs.cards.card>
    <x-vdbs.cards.card title="Zwei">Inhalt</x-vdbs.cards.card>
    <x-vdbs.cards.card title="Drei">Inhalt</x-vdbs.cards.card>
</x-vdbs.layout.grid>

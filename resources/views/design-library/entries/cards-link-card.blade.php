<p>Paket <strong>Karten</strong> · <code>&lt;x-vdbs.cards.link-card&gt;</code></p>

<h2>Karten in einem Raster, Farbe = Bereich</h2>
<x-vdbs.layout.grid :columns="3" align="start">
    <x-vdbs.cards.link-card title="Mitgliedschaften" href="#" icon="users" :tone="1" text="Anträge, Mitglieder und Austritte." />
    <x-vdbs.cards.link-card title="Sitzungen" href="#" icon="calendar" :tone="1" text="Planung, Tagesordnung und Protokoll.">
        <x-vdbs.data.badge tone="warning">2 offen</x-vdbs.data.badge>
    </x-vdbs.cards.link-card>
    <x-vdbs.cards.link-card title="Ohne Icon und Text" href="#" />
</x-vdbs.layout.grid>

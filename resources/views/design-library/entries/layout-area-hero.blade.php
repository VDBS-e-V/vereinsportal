<p>Paket <strong>Layout</strong> · <code>&lt;x-vdbs.layout.area-hero&gt;</code></p>

<h2>Mit Begrüßung, Aktionen und Karte rechts</h2>
<x-vdbs.layout.area-hero :area="\App\Support\PortalAreas::Vorstand" title="Übersicht für den Vorstand" greeting="Guten Tag, Erika" lead="Kennzahlen zum Verein und alles, was heute ansteht.">
    <x-slot:actions>
        <x-vdbs.actions.button href="#" icon="calendar">Sitzungen</x-vdbs.actions.button>
        <x-vdbs.actions.button href="#" variant="secondary" icon="users">Mitgliedschaften</x-vdbs.actions.button>
    </x-slot:actions>
    <x-slot:aside>
        <p><strong>Nächste Sitzung</strong></p>
        <p>12.12.2026, 19:00 Uhr</p>
    </x-slot:aside>
</x-vdbs.layout.area-hero>

<h2>Ohne Zusätze, andere Bereichsfarbe</h2>
<x-vdbs.layout.area-hero :area="\App\Support\PortalAreas::Teamende" title="Teamende" lead="Seminare, Ihr Profil und Ihre Einarbeitung." />

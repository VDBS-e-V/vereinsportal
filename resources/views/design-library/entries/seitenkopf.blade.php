<p>Paket <strong>Layout</strong> · <code>&lt;x-vdbs.layout.page-header&gt;</code></p>

<h2>Mit Brotkrumen, Status und Aktionen</h2>
<x-vdbs.layout.page-header title="Erika Mustermann" eyebrow="Nutzerverwaltung" lead="Mitglied seit 2021, Bereich Vorstand." :breadcrumb="[['label' => 'Start', 'href' => '#'], ['label' => 'Nutzer', 'href' => '#'], ['label' => 'Erika Mustermann']]">
    <x-slot:meta><x-vdbs.data.badge tone="success">Aktiv</x-vdbs.data.badge></x-slot:meta>
    <x-slot:actions>
        <x-vdbs.actions.button variant="secondary" icon="pencil">Bearbeiten</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="danger" icon="trash-2">Löschen</x-vdbs.actions.button>
    </x-slot:actions>
    <x-slot:tabs>
        <x-vdbs.navigation.tab-nav label="Nutzer" :items="[['label' => 'Profil', 'href' => '#', 'current' => true], ['label' => 'Rollen', 'href' => '#'], ['label' => 'Verlauf', 'href' => '#']]" />
    </x-slot:tabs>
</x-vdbs.layout.page-header>

<h2>Einfach</h2>
<x-vdbs.layout.page-header title="Einstellungen" lead="Konto, Sicherheit und Benachrichtigungen." />

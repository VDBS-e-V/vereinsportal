<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.toolbar&gt;</code>, <code>&lt;x-vdbs.data.filter-tag&gt;</code></p>
<p>Suche, Filter und aktive Filter als entfernbare Tags über Tabellen und Listen. Ein GET-Formular, die Filter stehen also in der Adresse.</p>

<h2>Ohne aktive Filter</h2>
<x-vdbs.data.toolbar label="Nutzer filtern" count="128 Nutzer" search-placeholder="Name oder E-Mail suchen">
    <x-vdbs.forms.select name="rolle" label="Rolle" placeholder="Alle" :options="['vorstand' => 'Vorstand', 'teamende' => 'Teamende', 'schulen' => 'Schulen']" />
    <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="['aktiv' => 'Aktiv', 'ausstehend' => 'Ausstehend', 'gesperrt' => 'Gesperrt']" />
    <x-slot:actions><x-vdbs.actions.button icon="plus">Nutzer anlegen</x-vdbs.actions.button></x-slot:actions>
</x-vdbs.data.toolbar>

<h2>Mit aktiven Filtern</h2>
<x-vdbs.data.toolbar label="Nutzer filtern" count="12 von 128 Nutzern" reset-href="#" :active="[
    ['label' => 'Rolle: Vorstand', 'href' => '#'],
    ['label' => 'Status: Aktiv', 'href' => '#'],
    ['label' => 'Suche: Muster', 'href' => '#'],
]">
    <x-vdbs.forms.select name="rolle2" label="Rolle" value="vorstand" :options="['vorstand' => 'Vorstand', 'teamende' => 'Teamende']" />
    <x-vdbs.forms.select name="status2" label="Status" value="aktiv" :options="['aktiv' => 'Aktiv', 'gesperrt' => 'Gesperrt']" />
</x-vdbs.data.toolbar>

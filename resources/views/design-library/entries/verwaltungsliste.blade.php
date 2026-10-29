<p>Zusammenspiel von Kennzahlen, Werkzeugleiste, Tabelle, Paginierung und Leerzustand für die Nutzerverwaltung.</p>

<x-vdbs.layout.grid :columns="4">
    <x-vdbs.data.stat label="Aktive Nutzer" value="128" icon="users" tone="success" />
    <x-vdbs.data.stat label="Offene Einladungen" value="7" icon="mail" />
    <x-vdbs.data.stat label="Ohne Zwei-Faktor" value="23" icon="shield" tone="warning" />
    <x-vdbs.data.stat label="Gesperrt" value="2" icon="lock" tone="error" />
</x-vdbs.layout.grid>

<div style="margin-top:var(--space-6)">
    <x-vdbs.data.toolbar label="Nutzer filtern" count="3 von 128 Nutzern" reset-href="#" :active="[['label' => 'Rolle: Vorstand', 'href' => '#']]" search-placeholder="Name oder E-Mail suchen">
        <x-vdbs.forms.select name="v_rolle" label="Rolle" value="vorstand" :options="['vorstand' => 'Vorstand', 'teamende' => 'Teamende']" />
        <x-slot:actions><x-vdbs.actions.button icon="plus">Nutzer anlegen</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.data.toolbar>

    <x-vdbs.data.table caption="Nutzer" sort="name" :columns="[
        ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'href' => '#'],
        ['key' => 'rolle', 'label' => 'Rolle', 'sortable' => true, 'href' => '#'],
        ['key' => 'status', 'label' => 'Status'],
    ]">
        <tr><td>Erika Mustermann</td><td>Vorstand</td><td><x-vdbs.data.badge tone="success">Aktiv</x-vdbs.data.badge></td></tr>
        <tr><td>Max Mustermann</td><td>Vorstand</td><td><x-vdbs.data.badge tone="warning">Ausstehend</x-vdbs.data.badge></td></tr>
        <tr><td>Lena Beispiel</td><td>Vorstand</td><td><x-vdbs.data.badge tone="error">Gesperrt</x-vdbs.data.badge></td></tr>
    </x-vdbs.data.table>

    <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :current="1" :last="5" href="#seite-:page" /></div>
</div>

<h2>Ohne Treffer</h2>
<x-vdbs.feedback.empty-state title="Keine Treffer" icon="search">
    <p>Zu den gewählten Filtern gibt es keine Nutzer.</p>
    <x-slot:actions><x-vdbs.actions.button variant="secondary">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
</x-vdbs.feedback.empty-state>

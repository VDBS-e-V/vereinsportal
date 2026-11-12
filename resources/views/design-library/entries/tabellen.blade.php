<x-vdbs.data.table caption="Beispieltabelle" sort="name" :columns="[
    ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'href' => '#'],
    ['key' => 'rolle', 'label' => 'Rolle', 'sortable' => true, 'href' => '#'],
    ['key' => 'status', 'label' => 'Status'],
]">
    <tr><td>Erika Musterfrau</td><td>Vorstand</td><td><x-vdbs.data.badge tone="success">Aktiv</x-vdbs.data.badge></td></tr>
    <tr><td>Max Mustermann</td><td>Teamende</td><td><x-vdbs.data.badge tone="warning">Ausstehend</x-vdbs.data.badge></td></tr>
    <tr><td>Lena Beispiel</td><td>Mitglied</td><td><x-vdbs.data.badge tone="error">Gesperrt</x-vdbs.data.badge></td></tr>
</x-vdbs.data.table>

<h3 style="margin-top:var(--space-6);font-size:var(--font-size-h4)">Klickbare Zeilen</h3>
<p>Der Link im Namen deckt die ganze Zeile ab: Ein Klick an jeder Stelle führt zum Datensatz.</p>
<x-vdbs.data.table caption="Tabelle mit klickbaren Zeilen" :columns="[
    ['key' => 'name', 'label' => 'Name'],
    ['key' => 'rolle', 'label' => 'Rolle'],
    ['key' => 'status', 'label' => 'Status'],
]">
    <tr class="vdbs-table__row--link"><td><a class="vdbs-table__row-link" href="#erika">Erika Musterfrau</a></td><td>Vorstand</td><td><x-vdbs.data.badge tone="success">Aktiv</x-vdbs.data.badge></td></tr>
    <tr class="vdbs-table__row--link"><td><a class="vdbs-table__row-link" href="#max">Max Mustermann</a></td><td>Teamende</td><td><x-vdbs.data.badge tone="warning">Ausstehend</x-vdbs.data.badge></td></tr>
</x-vdbs.data.table>

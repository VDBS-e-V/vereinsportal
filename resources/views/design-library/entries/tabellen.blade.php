<x-vdbs.data.table caption="Beispieltabelle" sort="name" :columns="[
    ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'href' => '#'],
    ['key' => 'rolle', 'label' => 'Rolle', 'sortable' => true, 'href' => '#'],
    ['key' => 'status', 'label' => 'Status'],
]">
    <tr><td>Erika Musterfrau</td><td>Vorstand</td><td><x-vdbs.data.badge tone="success">Aktiv</x-vdbs.data.badge></td></tr>
    <tr><td>Max Mustermann</td><td>Teamende</td><td><x-vdbs.data.badge tone="warning">Ausstehend</x-vdbs.data.badge></td></tr>
    <tr><td>Lena Beispiel</td><td>Mitglied</td><td><x-vdbs.data.badge tone="error">Gesperrt</x-vdbs.data.badge></td></tr>
</x-vdbs.data.table>

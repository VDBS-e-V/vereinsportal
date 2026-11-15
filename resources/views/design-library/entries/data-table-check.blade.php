<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.table-check&gt;</code> in <code>&lt;x-vdbs.data.table&gt;</code></p>

<h2>Mehrfachauswahl mit Kopfkästchen</h2>
<p>Das Kopfkästchen (<code>'selectAll' =&gt; '&lt;form-id&gt;'</code> in den Spalten) wählt alle Zeilen; die Zeilenkästchen gehören über <code>form="&lt;form-id&gt;"</code> zum Formular. Mit JavaScript zeigt das Kopfkästchen den gemischten Zustand, ohne JavaScript bleiben die Zeilenkästchen nutzbar.</p>
<form id="beispiel-auswahl" method="post" action="#" onsubmit="return false">
    <x-vdbs.data.table caption="Beispiel mit Mehrfachauswahl" :columns="[['key' => 'sel', 'label' => 'Auswahl', 'selectAll' => 'beispiel-auswahl'], ['key' => 'name', 'label' => 'Name'], ['key' => 'status', 'label' => 'Status']]">
        <tr><x-vdbs.data.table-check name="ids[]" value="1" label="Erika Musterfrau auswählen" form="beispiel-auswahl" /><td>Erika Musterfrau</td><td><x-vdbs.data.badge tone="success">Aktiv</x-vdbs.data.badge></td></tr>
        <tr><x-vdbs.data.table-check name="ids[]" value="2" label="Max Mustermann auswählen" form="beispiel-auswahl" /><td>Max Mustermann</td><td><x-vdbs.data.badge tone="warning">Ausstehend</x-vdbs.data.badge></td></tr>
        <tr><x-vdbs.data.table-check name="ids[]" value="3" label="Lena Beispiel auswählen" form="beispiel-auswahl" :checked="true" /><td>Lena Beispiel</td><td><x-vdbs.data.badge tone="neutral">Ausgeblendet</x-vdbs.data.badge></td></tr>
    </x-vdbs.data.table>
    <p><span class="vdbs-hint" role="status" data-vdbs-select-count="beispiel-auswahl"></span></p>
</form>

<h2>Einzelner Schalter je Zeile</h2>
<p>Ohne <code>form</code> und Kopfkästchen dient dasselbe Kästchen als Schalter innerhalb eines Formulars, z. B. „Gilt als erledigt“.</p>
<x-vdbs.data.table caption="Beispiel mit Schaltern" :columns="[['key' => 'name', 'label' => 'Spalte'], ['key' => 'done', 'label' => 'Gilt als erledigt']]">
    <tr><td>Offen</td><x-vdbs.data.table-check name="beispiel[offen]" label="Offen gilt als erledigt" /></tr>
    <tr><td>Fertig</td><x-vdbs.data.table-check name="beispiel[fertig]" label="Fertig gilt als erledigt" :checked="true" /></tr>
</x-vdbs.data.table>

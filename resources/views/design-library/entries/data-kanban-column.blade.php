<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.kanban-column&gt;</code></p>

<h2>Mit Karten und Fußbereich</h2>
<x-vdbs.data.kanban label="Beispiel">
    <x-vdbs.data.kanban-column title="Offen" :status-id="1" :count="1">
        <x-vdbs.data.kanban-card title="Beispielkarte" href="#" />
        <x-slot:footer><x-vdbs.forms.input name="demo" label="Neue Aufgabe" :label-hidden="true" placeholder="Neue Aufgabe" /></x-slot:footer>
    </x-vdbs.data.kanban-column>
    <x-vdbs.data.kanban-column title="Leer" :status-id="2" :count="0" />
</x-vdbs.data.kanban>

<h2>Erledigt-Spalte</h2>
<x-vdbs.data.kanban label="Beispiel">
    <x-vdbs.data.kanban-column title="Fertig" :status-id="3" :count="0" :done="true" />
</x-vdbs.data.kanban>

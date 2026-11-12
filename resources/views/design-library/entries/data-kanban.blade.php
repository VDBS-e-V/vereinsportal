<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.kanban&gt;</code> mit <code>&lt;x-vdbs.data.kanban-column&gt;</code> und <code>&lt;x-vdbs.data.kanban-card&gt;</code></p>

<p>Karten lassen sich per Ziehen und Ablegen in eine andere Spalte oder an eine andere Stelle verschieben (<code>resources/js/kanban.js</code>). Ohne JavaScript und per Tastatur bleibt das Formular „Verschieben“ an der Karte. Die Beispiele hier senden nichts.</p>

<h2>Standard</h2>
<x-vdbs.data.kanban label="Beispieltafel">
    <x-vdbs.data.kanban-column title="Offen" :status-id="1" :count="2">
        <x-vdbs.data.kanban-card title="Login prüfen" href="#"><x-vdbs.data.badge tone="error">Hoch</x-vdbs.data.badge></x-vdbs.data.kanban-card>
        <x-vdbs.data.kanban-card title="Texte korrigieren" href="#" />
    </x-vdbs.data.kanban-column>
    <x-vdbs.data.kanban-column title="In Arbeit" :status-id="2" :count="1">
        <x-vdbs.data.kanban-card title="Rundschreiben vorbereiten" href="#" priority="high">Fällig am 30.11.2026</x-vdbs.data.kanban-card>
    </x-vdbs.data.kanban-column>
    <x-vdbs.data.kanban-column title="Erledigt" :status-id="3" :count="0" :done="true" />
</x-vdbs.data.kanban>

<h2>Dunkelmodus</h2>
<p>Spalten und Karten nutzen nur semantische Tokens und folgen dem Farbschema des Portals.</p>

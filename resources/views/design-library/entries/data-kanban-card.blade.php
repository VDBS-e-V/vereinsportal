<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.kanban-card&gt;</code></p>

<h2>Normal und hohe Priorität</h2>
<ul style="list-style:none;padding:0;display:grid;gap:var(--space-3);max-width:20rem">
    <x-vdbs.data.kanban-card title="Normale Karte" href="#">Fällig am 30.11.2026</x-vdbs.data.kanban-card>
    <x-vdbs.data.kanban-card title="Wichtige Karte" href="#" priority="high"><x-vdbs.data.badge tone="error">Überfällig</x-vdbs.data.badge></x-vdbs.data.kanban-card>
    <x-vdbs.data.kanban-card title="Karte ohne Link" />
</ul>

<h2>Verschiebbar</h2>
<p>Mit <code>move-url</code> ist die Karte per Ziehen verschiebbar (hier nur zur Ansicht, ohne Adresse).</p>

<p>Paket <strong>Feedback</strong> · <code>&lt;x-vdbs.feedback.empty-state&gt;</code></p>

<h2>Standard</h2>
<x-vdbs.feedback.empty-state title="Noch keine Nutzer" icon="users">
    <p>Sobald Nutzerkonten angelegt sind, erscheinen sie hier.</p>
    <x-slot:actions>
        <x-vdbs.actions.button icon="plus">Nutzer anlegen</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="secondary">Einladung senden</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.feedback.empty-state>

<h2>Keine Treffer</h2>
<x-vdbs.feedback.empty-state title="Keine Treffer" icon="search">
    <p>Zu deiner Suche gibt es keine Ergebnisse. Prüfe die Schreibweise oder entferne Filter.</p>
    <x-slot:actions><x-vdbs.actions.button variant="secondary">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
</x-vdbs.feedback.empty-state>

<h2>Kompakt (in einer Karte)</h2>
<div style="max-width:24rem">
    <x-vdbs.feedback.empty-state size="s" title="Keine Termine" icon="calendar"><p>Aktuell sind keine Termine geplant.</p></x-vdbs.feedback.empty-state>
</div>

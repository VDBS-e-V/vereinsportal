<p>Paket <strong>Overlays</strong> · <code>&lt;x-vdbs.overlays.drawer&gt;</code></p>
<p>Seitenpanel für Filter am Handy, Details zu einer Zeile oder kurze Formulare. Esc, Klick daneben und das Kreuz schließen; der Fokus kehrt zum Auslöser zurück.</p>

<div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
    <x-vdbs.actions.button variant="secondary" icon="filter" data-vdbs-dialog-open="drawer-filter">Filter (rechts)</x-vdbs.actions.button>
    <x-vdbs.actions.button variant="secondary" icon="menu" data-vdbs-dialog-open="drawer-start">Navigation (links, klein)</x-vdbs.actions.button>
</div>

<x-vdbs.overlays.drawer id="drawer-filter" title="Filter">
    <x-vdbs.forms.select name="d_rolle" label="Rolle" placeholder="Alle" :options="['vorstand' => 'Vorstand', 'teamende' => 'Teamende']" />
    <x-vdbs.forms.select name="d_status" label="Status" placeholder="Alle" :options="['aktiv' => 'Aktiv', 'gesperrt' => 'Gesperrt']" />
    <x-slot:actions>
        <x-vdbs.actions.button variant="secondary" data-vdbs-dialog-close>Abbrechen</x-vdbs.actions.button>
        <x-vdbs.actions.button data-vdbs-dialog-close>Anwenden</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.overlays.drawer>

<x-vdbs.overlays.drawer id="drawer-start" title="Konto" side="start" size="s">
    <x-vdbs.navigation.side-nav label="Konto" :sections="[['label' => 'Profil', 'href' => '#', 'icon' => 'user', 'current' => true], ['label' => 'Sicherheit', 'href' => '#', 'icon' => 'shield']]" />
</x-vdbs.overlays.drawer>

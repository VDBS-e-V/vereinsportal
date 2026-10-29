<p>Paket <strong>Overlays</strong> · <code>&lt;x-vdbs.overlays.confirm-dialog&gt;</code></p>
<p>Für folgenreiche Aktionen. Der Fokus startet auf „Abbrechen“. Mit <code>phrase</code> wird die Bestätigung erst nach der Eingabe des Textes freigegeben; der Server prüft das Feld <code>confirmation</code> zusätzlich.</p>

<div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
    <x-vdbs.actions.button variant="secondary" data-vdbs-dialog-open="confirm-default">Einfach bestätigen</x-vdbs.actions.button>
    <x-vdbs.actions.button variant="danger" icon="trash-2" data-vdbs-dialog-open="confirm-danger">Löschen mit Eingabe</x-vdbs.actions.button>
</div>

<x-vdbs.overlays.confirm-dialog id="confirm-default" title="Einladung senden?" action="#" confirm-label="Einladung senden">
    <p>Die Person erhält eine E-Mail mit einem Link zur Anmeldung. Der Link ist sieben Tage gültig.</p>
</x-vdbs.overlays.confirm-dialog>

<x-vdbs.overlays.confirm-dialog id="confirm-danger" title="Nutzer löschen?" variant="danger" action="#" method="delete" confirm-label="Endgültig löschen" phrase="LÖSCHEN">
    <p>Das Konto von <strong>Erika Mustermann</strong> und alle zugehörigen Zuordnungen werden entfernt. Das lässt sich nicht rückgängig machen.</p>
</x-vdbs.overlays.confirm-dialog>

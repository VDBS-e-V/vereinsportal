<x-vdbs.actions.button variant="secondary" data-vdbs-dialog-open="demo-modal">Modal öffnen</x-vdbs.actions.button>
<x-vdbs.overlays.modal id="demo-modal" title="Beispiel-Modal">
    <p>Natives <code>dialog</code>-Element: Fokus bleibt im Dialog, Esc schließt, der Fokus kehrt zum Auslöser zurück.</p>
    <x-slot:actions>
        <x-vdbs.actions.button variant="secondary" data-vdbs-dialog-close>Abbrechen</x-vdbs.actions.button>
        <x-vdbs.actions.button data-vdbs-dialog-close>Bestätigen</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.overlays.modal>

<x-vdbs.button variant="secondary" data-vdbs-dialog-open="demo-modal">Modal öffnen</x-vdbs.button>
<x-vdbs.modal id="demo-modal" title="Beispiel-Modal">
    <p>Natives <code>dialog</code>-Element: Fokus bleibt im Dialog, Esc schließt, der Fokus kehrt zum Auslöser zurück.</p>
    <x-slot:actions>
        <x-vdbs.button variant="secondary" data-vdbs-dialog-close>Abbrechen</x-vdbs.button>
        <x-vdbs.button data-vdbs-dialog-close>Bestätigen</x-vdbs.button>
    </x-slot:actions>
</x-vdbs.modal>

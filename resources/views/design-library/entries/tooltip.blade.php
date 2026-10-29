<p>Paket <strong>Feedback</strong> · <code>&lt;x-vdbs.feedback.tooltip&gt;</code></p>
<p>Erscheint bei Hover und Fokus, Esc blendet aus. Das Element im Slot muss per Tastatur erreichbar sein; wichtige Informationen gehören nicht nur in einen Tooltip, weil es auf Touch-Geräten kein Hover gibt.</p>

<div style="display:flex;gap:var(--space-8);padding:var(--space-8) var(--space-6);flex-wrap:wrap">
    <x-vdbs.feedback.tooltip text="Eintrag bearbeiten"><x-vdbs.actions.button variant="quiet" icon="pencil" label="Bearbeiten" /></x-vdbs.feedback.tooltip>
    <x-vdbs.feedback.tooltip text="Löschen kann nicht rückgängig gemacht werden" placement="bottom"><x-vdbs.actions.button variant="danger" icon="trash-2" label="Löschen" /></x-vdbs.feedback.tooltip>
    <x-vdbs.feedback.tooltip text="Links angezeigt" placement="start"><x-vdbs.actions.button variant="secondary">Links</x-vdbs.actions.button></x-vdbs.feedback.tooltip>
    <x-vdbs.feedback.tooltip text="Rechts angezeigt" placement="end"><x-vdbs.actions.button variant="secondary">Rechts</x-vdbs.actions.button></x-vdbs.feedback.tooltip>
</div>

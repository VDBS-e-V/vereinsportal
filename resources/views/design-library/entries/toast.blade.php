<p>Paket <strong>Feedback</strong> · <code>&lt;x-vdbs.feedback.toast&gt;</code>, <code>&lt;x-vdbs.feedback.toast-region&gt;</code>, <code>&lt;x-vdbs.feedback.flash&gt;</code></p>
<p>Im Seitenrahmen steht <code>&lt;x-vdbs.feedback.flash /&gt;</code>: Meldungen aus <code>return back()-&gt;with('success', 'Gespeichert.')</code> erscheinen automatisch (Schlüssel <code>success</code>, <code>status</code>, <code>info</code>, <code>warning</code>, <code>error</code>). Mit JavaScript schweben sie unten rechts; Erfolgsmeldungen schließen sich nach 8 Sekunden (pausiert bei Hover und Fokus), Warnungen und Fehler bleiben stehen. Hier die Varianten im Inhalt:</p>

<div style="display:grid;gap:var(--space-3);max-width:28rem">
    <x-vdbs.feedback.toast variant="success" :timeout="0">Profil gespeichert.</x-vdbs.feedback.toast>
    <x-vdbs.feedback.toast variant="info" :timeout="0" title="Hinweis">Die Wartung beginnt um 22 Uhr.</x-vdbs.feedback.toast>
    <x-vdbs.feedback.toast variant="warning" title="Fast voll">Der Speicher ist zu 90 % belegt.</x-vdbs.feedback.toast>
    <x-vdbs.feedback.toast variant="error" title="Nicht gespeichert">Bitte prüfe die markierten Felder.</x-vdbs.feedback.toast>
</div>

<p>Paket <strong>Feedback</strong> · <code>&lt;x-vdbs.feedback.skeleton&gt;</code>, <code>&lt;x-vdbs.feedback.progress&gt;</code></p>

<h2>Skeleton</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:var(--space-5)">
    <x-vdbs.feedback.skeleton variant="card" label="Karte wird geladen …" />
    <div style="display:grid;gap:var(--space-3)">
        <x-vdbs.feedback.skeleton variant="title" />
        <x-vdbs.feedback.skeleton :lines="4" />
    </div>
    <div style="display:flex;gap:var(--space-3);align-items:center">
        <x-vdbs.feedback.skeleton variant="avatar" />
        <div style="flex:1"><x-vdbs.feedback.skeleton :lines="2" /></div>
    </div>
</div>

<h2>Fortschritt</h2>
<div style="display:grid;gap:var(--space-5);max-width:28rem">
    <x-vdbs.feedback.progress label="Upload" :value="64" />
    <x-vdbs.feedback.progress label="Import abgeschlossen" :value="100" />
    <x-vdbs.feedback.progress label="Wird verarbeitet" />
</div>

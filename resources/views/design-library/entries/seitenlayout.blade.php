<h2>Container-Breiten</h2>
<p>Die Breite des Inhalts bestimmt die Seite über <code>&lt;x-layouts.app width="…"&gt;</code> oder einzelne Bereiche über <code>&lt;x-vdbs.layout.container width="…"&gt;</code>.</p>

<x-vdbs.data.table caption="Container-Breiten" :columns="[
    ['key' => 'width', 'label' => 'width'],
    ['key' => 'max', 'label' => 'Maximale Breite'],
    ['key' => 'token', 'label' => 'Token'],
    ['key' => 'use', 'label' => 'Verwendung'],
]">
    <tr><th scope="row"><code>narrow</code></th><td>40 rem (640 px)</td><td><code>--container-narrow</code></td><td>Login, Formulare, Lesetext</td></tr>
    <tr><th scope="row"><code>medium</code></th><td>56 rem (896 px)</td><td><code>--container-medium</code></td><td>Artikel, Konto</td></tr>
    <tr><th scope="row"><code>default</code></th><td>72 rem (1152 px)</td><td><code>--container-default</code></td><td>Standardseiten</td></tr>
    <tr><th scope="row"><code>wide</code></th><td>90 rem (1440 px)</td><td><code>--container-wide</code></td><td>Tabellen, Verwaltung</td></tr>
    <tr><th scope="row"><code>full</code></th><td>keine Begrenzung</td><td>–</td><td>Startseite: Abschnitte und Hero bringen eigene Container mit</td></tr>
</x-vdbs.data.table>

<p>Der Seitenrand (<code>--gutter</code>) beträgt 16 px auf dem Handy und ab 768 px 24 px.</p>

<h2>Breiten im Vergleich</h2>
@foreach (['narrow', 'medium', 'default', 'wide'] as $w)
    <div style="margin-bottom:var(--space-2);max-width:var(--container-{{ $w }});background:var(--bg-accent-soft);border-left:6px solid var(--bg-brand);padding:var(--space-2) var(--space-3)">
        <code>{{ $w }}</code>
    </div>
@endforeach

<h2>Seitenvorlagen</h2>
<ul>
    <li><strong>Standard</strong>: <code>width="default"</code>, ein Container, Inhalt untereinander (Konto, Listen).</li>
    <li><strong>Fokus</strong>: <code>width="narrow"</code>, zentriert (Login, 2FA, Formulare).</li>
    <li><strong>Verwaltung</strong>: <code>width="wide"</code> mit Seitenleiste (<code>.vdbs-layout-sidebar</code>, 16 rem + Rest) für Tabellen.</li>
    <li><strong>Startseite</strong>: <code>width="full"</code>, Abschnitte als Bänder über die volle Breite.</li>
</ul>

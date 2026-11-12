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

<h2>Mit Bild, Fläche oder Seitenleiste</h2>
<p><code>&lt;x-vdbs.layout.aside-layout&gt;</code> stellt Inhalt neben ein Bild (<code>variant="image"</code>), eine Fläche (<code>panel</code>) oder eine Seitenleiste (<code>sidebar</code>). Beide Seiten sind möglich (<code>side="start|end"</code>), die Spaltenbreite ist <code>s</code>, <code>m</code>, <code>l</code> oder <code>half</code>, und am Handy lässt sich die Zusatzspalte oberhalb, unterhalb oder gar nicht zeigen (<code>mobile</code>).</p>

<h3>Bild rechts (<code>half</code>)</h3>
<x-vdbs.layout.aside-layout variant="image" size="half" main-width="narrow" mobile="above">
    <h4>Inhalt</h4>
    <p>Das Formular oder der Text steht in der linken Spalte, das Bild füllt die rechte.</p>
    <x-slot:aside><x-vdbs.media.image :src="placeholder('split', 1)" alt="" credit-placement="edge" /></x-slot:aside>
</x-vdbs.layout.aside-layout>

<h3>Bild links (<code>m</code>)</h3>
<x-vdbs.layout.aside-layout variant="image" side="start" size="m">
    <h4>Inhalt</h4>
    <p>Das Bild steht links in einer schmaleren Spalte.</p>
    <x-slot:aside><x-vdbs.media.image :src="placeholder('split', 2)" alt="" credit-placement="edge" /></x-slot:aside>
</x-vdbs.layout.aside-layout>

<h3>Seitenleiste (<code>sidebar</code>, <code>s</code>)</h3>
<x-vdbs.layout.aside-layout variant="sidebar" side="start" size="s" aside-label="Konto-Navigation" :sticky="true">
    <h4>Mein Konto</h4>
    <p>Der Inhalt steht rechts neben der Navigation. Die Leiste bleibt beim Scrollen stehen (<code>sticky</code>).</p>
    <x-slot:aside>
        <ul class="vdbs-sidebar">
            <li><a href="#" aria-current="page"><x-vdbs.icon name="user" /> Profil</a></li>
            <li><a href="#"><x-vdbs.icon name="lock" /> Sicherheit</a></li>
            <li><a href="#"><x-vdbs.icon name="mail" /> E-Mail</a></li>
        </ul>
    </x-slot:aside>
</x-vdbs.layout.aside-layout>

<h3>Fläche rechts (<code>panel</code>)</h3>
<x-vdbs.layout.aside-layout variant="panel" size="m" aside-label="Hilfe">
    <h4>Inhalt</h4>
    <p>Hinweise oder Hilfe stehen in einer Fläche neben dem Inhalt.</p>
    <x-slot:aside><strong>Hilfe</strong><p>Fragen? Schreiben Sie uns.</p></x-slot:aside>
</x-vdbs.layout.aside-layout>

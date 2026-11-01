@php
    $roles = ['primary' => 'Primär', 'secondary' => 'Sekundär', 'tertiary' => 'Tertiär', 'text' => 'Text', 'danger' => 'Gefahr'];
    $tones = ['green' => 'Grün', 'lila' => 'Lila'];
    $states = ['Normal' => [], 'Deaktiviert' => ['disabled' => true], 'Lädt' => ['loading' => true]];
@endphp

<p>Paket <strong>Aktionen</strong> · <code>&lt;x-vdbs.actions.button&gt;</code> ist der einzige Button-Baustein. Rolle (<code>variant</code>), Tonfarbe (<code>tone</code>), Größe und Fläche sind unabhängig kombinierbar. Mit <code>href</code> entsteht ein Link, sonst ein Button.</p>

<h2>Die Rollen in Grün und Lila</h2>
<ul>
    <li><strong>Primär</strong> (<code>primary</code>): dunkle Fläche, weiße Schrift. Pro Bereich höchstens eine.</li>
    <li><strong>Sekundär</strong> (<code>secondary</code>): helle Fläche, Ink-Schrift.</li>
    <li><strong>Tertiär</strong> (<code>tertiary</code>): weiße Fläche, Schrift und Rahmen in Farbe.</li>
    <li><strong>Text</strong> (<code>text</code>): nur Schrift in Farbe; Fläche und Unterstrich erst bei Hover.</li>
    <li><strong>Gefahr</strong> (<code>danger</code>): für unwiderrufliche Aktionen, immer mit Text.</li>
</ul>
<div class="vdbs-table-wrap">
    <table class="vdbs-table">
        <caption class="vdbs-sr-only">Rollen in den Tonfarben</caption>
        <thead><tr><th scope="col">Rolle</th>@foreach ($tones as $tone => $toneName)<th scope="col">{{ $toneName }} (<code>tone="{{ $tone }}"</code>)</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($roles as $variant => $name)
                <tr>
                    <th scope="row">{{ $name }}<br><code>{{ $variant }}</code></th>
                    @foreach ($tones as $tone => $toneName)
                        <td>
                            @if ($variant === 'danger')
                                @if ($tone === 'green') <x-vdbs.actions.button variant="danger" icon="trash-2">Löschen</x-vdbs.actions.button> @else <span style="color:var(--text-muted)">ohne Tonfarbe</span> @endif
                            @else
                                <x-vdbs.actions.button :variant="$variant" :tone="$tone">Aktion</x-vdbs.actions.button>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<h2>Zustände je Rolle und Tonfarbe</h2>
<div class="vdbs-table-wrap">
    <table class="vdbs-table">
        <caption class="vdbs-sr-only">Zustände</caption>
        <thead><tr><th scope="col">Rolle</th>@foreach ($states as $stateName => $props)<th scope="col">{{ $stateName }}</th>@endforeach<th scope="col">Gedrückt (Umschalter)</th></tr></thead>
        <tbody>
            @foreach ($tones as $tone => $toneName)
                @foreach (['primary', 'secondary', 'tertiary', 'text'] as $variant)
                    <tr>
                        <th scope="row">{{ $toneName }} · {{ $roles[$variant] }}</th>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone">Aktion</x-vdbs.actions.button></td>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone" :disabled="true">Aktion</x-vdbs.actions.button></td>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone" :loading="true">Sendet</x-vdbs.actions.button></td>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone" :pressed="true" icon="eye">Aktiv</x-vdbs.actions.button></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
<p>Hover und Aktiv (gedrückte Maustaste): Maus. Fokus: Tab-Taste. Auf dunklen Flächen wird der Fokusring weiß.</p>

<h2>Icons mit und ohne Text</h2>
<div class="vdbs-table-wrap">
    <table class="vdbs-table">
        <caption class="vdbs-sr-only">Icon-Formen je Rolle und Tonfarbe</caption>
        <thead><tr><th scope="col">Rolle</th><th scope="col">Nur Text</th><th scope="col">Icon links</th><th scope="col">Icon rechts</th><th scope="col">Nur Icon</th></tr></thead>
        <tbody>
            @foreach ($tones as $tone => $toneName)
                @foreach (['primary', 'secondary', 'tertiary', 'text'] as $variant)
                    <tr>
                        <th scope="row">{{ $toneName }} · {{ $roles[$variant] }}</th>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone">Speichern</x-vdbs.actions.button></td>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone" icon="plus">Neu</x-vdbs.actions.button></td>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone" icon="arrow-right" icon-position="end">Weiter</x-vdbs.actions.button></td>
                        <td><x-vdbs.actions.button :variant="$variant" :tone="$tone" icon="pencil" label="Bearbeiten" /></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
<p>Es ist immer ein Icon vor oder nach dem Text möglich. Reine Icon-Buttons sind 44 × 44 px und brauchen <code>label</code> (wird <code>aria-label</code> und <code>title</code>). Gefahr-Buttons tragen Icon <em>und</em> Text, außer in Tabellenzeilen mit Label.</p>

<h2>Sonderformen</h2>
<p>Die vier Rollen haben ihre Form eingebaut (Primär und Sekundär gefüllt, Tertiär mit Rahmen, Text ohne Rahmen). <code>shape</code> brauchen Sie nur für diese Sonderfälle:</p>
<div class="vdbs-table-wrap">
    <table class="vdbs-table">
        <caption class="vdbs-sr-only">Sonderformen</caption>
        <thead><tr><th scope="col">Sonderform</th><th scope="col">Grün</th><th scope="col">Lila</th><th scope="col">Einsatz</th></tr></thead>
        <tbody>
            <tr>
                <th scope="row">Pfeil, primär<br><code>shape="arrow"</code></th>
                <td><x-vdbs.actions.button shape="arrow">Weiter</x-vdbs.actions.button></td>
                <td><x-vdbs.actions.button shape="arrow" tone="lila">Weiter</x-vdbs.actions.button></td>
                <td>Große Aufrufe auf Startseiten (Kurzform <code>link-button</code>)</td>
            </tr>
            <tr>
                <th scope="row">Pfeil, sekundär<br><code>variant="secondary" shape="arrow"</code></th>
                <td><x-vdbs.actions.button variant="secondary" shape="arrow">Weiter</x-vdbs.actions.button></td>
                <td><x-vdbs.actions.button variant="secondary" shape="arrow" tone="lila">Weiter</x-vdbs.actions.button></td>
                <td>Zweiter Aufruf neben dem primären</td>
            </tr>
            <tr>
                <th scope="row">Gefahr, zurückhaltend<br><code>variant="danger" shape="outline"</code></th>
                <td colspan="2"><x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2">Löschen</x-vdbs.actions.button></td>
                <td>Gefahr-Aktion neben einem Primär-Button, immer mit Text</td>
            </tr>
            <tr>
                <th scope="row">Link-Button<br><code>link-button variant="all"</code></th>
                <td colspan="2"><x-vdbs.actions.link-button href="#" variant="all">Alle anzeigen</x-vdbs.actions.link-button></td>
                <td>„Alle anzeigen“ am Ende einer Liste</td>
            </tr>
            <tr>
                <th scope="row">Linie<br><code>link-button variant="line"</code></th>
                <td colspan="2"><x-vdbs.actions.link-button href="#" variant="line">Mehr erfahren</x-vdbs.actions.link-button></td>
                <td>Teaser mit Linie unten</td>
            </tr>
        </tbody>
    </table>
</div>

<h2>Größen</h2>
<div style="display:flex;flex-wrap:wrap;gap:var(--space-4);align-items:center;margin-bottom:var(--space-4)">
    @foreach (['s', 'm', 'l', 'xl'] as $size)
        <x-vdbs.actions.button :size="$size">Größe {{ $size }}</x-vdbs.actions.button>
    @endforeach
</div>
<div style="display:flex;flex-wrap:wrap;gap:var(--space-4);align-items:center">
    @foreach (['s', 'm', 'l', 'xl'] as $size)
        <x-vdbs.actions.button :size="$size" shape="arrow">Weiter</x-vdbs.actions.button>
    @endforeach
</div>

<h2>Flächen (<code>on</code>)</h2>
@foreach (['default' => 'background:var(--bg-page)', 'dark' => 'background:var(--bg-header-footer);color:var(--text-on-dark)', 'brand' => 'background:var(--bg-brand);color:var(--text-on-brand)'] as $on => $style)
    <div style="{{ $style }};padding:var(--space-5);margin-bottom:var(--space-3);border:1px solid var(--border-default)">
        <p style="margin:0 0 var(--space-3)"><code>on="{{ $on }}"</code></p>
        <x-vdbs.actions.button-group>
            @foreach ($roles as $variant => $name)
                <x-vdbs.actions.button :variant="$variant" :on="$on">{{ $name }}</x-vdbs.actions.button>
            @endforeach
            <x-vdbs.actions.button variant="secondary" shape="line" :on="$on">Linie</x-vdbs.actions.button>
            <x-vdbs.actions.button shape="arrow" :on="$on">Pfeil</x-vdbs.actions.button>
        </x-vdbs.actions.button-group>
    </div>
@endforeach
<p>Alternativ gilt eine Fläche für alle Buttons darin: Container mit <code>.vdbs-on-dark</code> oder <code>.vdbs-on-brand</code> (so lösen die Heros es).</p>

<h2>Gruppe und volle Breite</h2>
<x-vdbs.actions.button-group label="Formular-Aktionen" align="end">
    <x-vdbs.actions.button variant="text">Abbrechen</x-vdbs.actions.button>
    <x-vdbs.actions.button variant="secondary">Entwurf speichern</x-vdbs.actions.button>
    <x-vdbs.actions.button>Speichern</x-vdbs.actions.button>
</x-vdbs.actions.button-group>
<div style="max-width:24rem;margin-top:var(--space-4)"><x-vdbs.actions.button :full="true" size="l">Volle Breite</x-vdbs.actions.button></div>

<h2>Eigenschaften</h2>
<x-vdbs.data.table caption="Eigenschaften des Buttons" :columns="[
    ['key' => 'p', 'label' => 'Eigenschaft'],
    ['key' => 'v', 'label' => 'Werte'],
    ['key' => 'd', 'label' => 'Bedeutung'],
]">
    <tr><th scope="row"><code>variant</code></th><td>primary, secondary, tertiary, text, danger</td><td>Wichtigkeit der Aktion (<code>quiet</code> ist der alte Name von <code>text</code>)</td></tr>
    <tr><th scope="row"><code>tone</code></th><td>green (Standard), lila</td><td>Tonfarbe von primary, secondary, tertiary und text</td></tr>
    <tr><th scope="row"><code>shape</code></th><td>arrow, line, link, outline, text</td><td>Nur für Sonderformen (siehe oben); jede Rolle bringt ihre Form mit</td></tr>
    <tr><th scope="row"><code>size</code></th><td>s, m (Standard), l, xl</td><td>36, 44, 52, 60 px Höhe</td></tr>
    <tr><th scope="row"><code>on</code></th><td>default (Standard), dark, brand</td><td>Fläche hinter dem Button; default = normale Seitenfläche, folgt dem Theme</td></tr>
    <tr><th scope="row"><code>href</code></th><td>URL</td><td>Link statt Button</td></tr>
    <tr><th scope="row"><code>icon</code>, <code>icon-position</code></th><td>Lucide-Name; start, end</td><td>Icon mit Text</td></tr>
    <tr><th scope="row"><code>label</code></th><td>Text</td><td>Pflicht bei reinen Icon-Buttons</td></tr>
    <tr><th scope="row"><code>loading</code>, <code>disabled</code>, <code>pressed</code></th><td>true / false</td><td>Zustände (aria-busy, aria-pressed)</td></tr>
    <tr><th scope="row"><code>full</code>, <code>external</code></th><td>true / false</td><td>Volle Breite; neuer Tab mit Hinweis</td></tr>
</x-vdbs.data.table>

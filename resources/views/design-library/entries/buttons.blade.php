@php
    $roles = ['primary' => 'Primär', 'secondary' => 'Sekundär', 'tertiary' => 'Tertiär', 'danger' => 'Gefahr', 'quiet' => 'Leise'];
    $shapes = ['solid', 'outline', 'link', 'arrow', 'line'];
@endphp

<p>Paket <strong>Aktionen</strong> · <code>&lt;x-vdbs.actions.button&gt;</code> ist der einzige Button-Baustein. Rolle, Form, Größe und Fläche sind unabhängig kombinierbar. Mit <code>href</code> entsteht ein Link, sonst ein Button.</p>

<h2>Rollen × Formen</h2>
<div class="vdbs-table-wrap">
    <table class="vdbs-table">
        <caption class="vdbs-sr-only">Rollen und Formen</caption>
        <thead>
            <tr>
                <th scope="col">Rolle</th>
                @foreach ($shapes as $shape) <th scope="col"><code>{{ $shape }}</code></th> @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $variant => $name)
                <tr>
                    <th scope="row">{{ $name }}<br><code>{{ $variant }}</code></th>
                    @foreach ($shapes as $shape)
                        <td><x-vdbs.actions.button :variant="$variant" :shape="$shape" :icon="$variant === 'danger' ? 'trash-2' : null">Aktion</x-vdbs.actions.button></td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p>Standardform je Rolle: primary = solid, secondary = outline, tertiary = link, danger = solid, quiet = link. Pro Bereich höchstens eine primäre Aktion.</p>

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

<h2>Icons</h2>
<x-vdbs.actions.button-group>
    <x-vdbs.actions.button icon="plus">Icon links</x-vdbs.actions.button>
    <x-vdbs.actions.button icon="arrow-right" icon-position="end" variant="secondary">Icon rechts</x-vdbs.actions.button>
    <x-vdbs.actions.button icon="trash-2" variant="danger" label="Löschen" />
    <x-vdbs.actions.button icon="pencil" variant="secondary" label="Bearbeiten" size="s" />
    <x-vdbs.actions.button href="https://example.org" :external="true" variant="tertiary">Externer Link</x-vdbs.actions.button>
</x-vdbs.actions.button-group>
<h3>Icons in jeder Form</h3>
<div class="vdbs-table-wrap">
    <table class="vdbs-table">
        <caption class="vdbs-sr-only">Icons je Form</caption>
        <thead><tr><th scope="col">Form</th><th scope="col">Icon links</th><th scope="col">Icon rechts</th><th scope="col">Nur Icon</th></tr></thead>
        <tbody>
            @foreach ($shapes as $shape)
                <tr>
                    <th scope="row"><code>{{ $shape }}</code></th>
                    <td><x-vdbs.actions.button :shape="$shape" icon="plus">Neu</x-vdbs.actions.button></td>
                    <td><x-vdbs.actions.button :shape="$shape" icon="download" icon-position="end">Laden</x-vdbs.actions.button></td>
                    <td>@if ($shape === 'link') <span style="color:var(--text-muted)">nicht sinnvoll</span> @else <x-vdbs.actions.button :shape="$shape" icon="pencil" label="Bearbeiten" /> @endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p>Es ist immer ein Icon vor oder nach dem Text möglich. Die Pfeil-Form hat zusätzlich immer den Pfeil am Ende; die Linien-Form zeigt ohne eigenes Icon einen Chevron am Anfang.</p>

<p>Reine Icon-Buttons brauchen <code>label</code> (wird <code>aria-label</code> und <code>title</code>). Gefahr-Buttons tragen immer Icon <em>und</em> Text, außer in Tabellenzeilen mit Label.</p>

<h2>Zustände</h2>
<x-vdbs.actions.button-group>
    <x-vdbs.actions.button>Normal</x-vdbs.actions.button>
    <x-vdbs.actions.button :disabled="true">Deaktiviert</x-vdbs.actions.button>
    <x-vdbs.actions.button :loading="true">Lädt</x-vdbs.actions.button>
    <x-vdbs.actions.button variant="secondary" :pressed="true" icon="eye">Gedrückt</x-vdbs.actions.button>
    <x-vdbs.actions.button variant="secondary" :pressed="false" icon="eye">Nicht gedrückt</x-vdbs.actions.button>
    <x-vdbs.actions.button href="#" :disabled="true" variant="secondary">Link deaktiviert</x-vdbs.actions.button>
</x-vdbs.actions.button-group>
<p>Fokus: Tab-Taste drücken. Hover und Aktiv: Maus. Auf dunklen Flächen wird der Fokusring weiß.</p>

<h2>Gruppe und volle Breite</h2>
<x-vdbs.actions.button-group label="Formular-Aktionen" align="end">
    <x-vdbs.actions.button variant="tertiary">Abbrechen</x-vdbs.actions.button>
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
    <tr><th scope="row"><code>variant</code></th><td>primary, secondary, tertiary, danger, quiet</td><td>Wichtigkeit der Aktion</td></tr>
    <tr><th scope="row"><code>shape</code></th><td>solid, outline, link, arrow, line</td><td>Form; Standard je Rolle</td></tr>
    <tr><th scope="row"><code>size</code></th><td>s, m (Standard), l, xl</td><td>36, 44, 52, 60 px Höhe</td></tr>
    <tr><th scope="row"><code>on</code></th><td>default (Standard), dark, brand</td><td>Fläche hinter dem Button; default = normale Seitenfläche, folgt dem Theme</td></tr>
    <tr><th scope="row"><code>href</code></th><td>URL</td><td>Link statt Button</td></tr>
    <tr><th scope="row"><code>icon</code>, <code>icon-position</code></th><td>Lucide-Name; start, end</td><td>Icon mit Text</td></tr>
    <tr><th scope="row"><code>label</code></th><td>Text</td><td>Pflicht bei reinen Icon-Buttons</td></tr>
    <tr><th scope="row"><code>loading</code>, <code>disabled</code>, <code>pressed</code></th><td>true / false</td><td>Zustände (aria-busy, aria-pressed)</td></tr>
    <tr><th scope="row"><code>full</code>, <code>external</code></th><td>true / false</td><td>Volle Breite; neuer Tab mit Hinweis</td></tr>
</x-vdbs.data.table>

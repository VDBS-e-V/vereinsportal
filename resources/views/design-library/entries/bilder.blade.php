@php
    $manifest = \App\Support\Media\ImageManifest::make()->entries();
@endphp

<p>Paket <strong>Medien</strong> · <code>&lt;x-vdbs.media.image&gt;</code>, <code>&lt;x-vdbs.media.figure&gt;</code> und <code>&lt;x-vdbs.media.image-credit&gt;</code>. <strong>Jedes Bild braucht eine Quelle und einen Alt-Text.</strong></p>

<h2>Quelle: zwei Anzeigen</h2>
<x-vdbs.layout.grid :columns="2">
    <div>
        <h3><code>below</code> – unter dem Bild</h3>
        <x-vdbs.media.figure :src="placeholder('split', 1)" alt="Zwei Personen sitzen in einem Klassenraum" caption="Eine Unterschrift erklärt das Bild." ratio="3-2" />
    </div>
    <div>
        <h3><code>edge</code> – Etikett am Bildrand</h3>
        <x-vdbs.media.figure :src="placeholder('split', 2)" alt="Unterricht in einem Klassenraum" caption="Die Quelle steht senkrecht am rechten Rand." ratio="3-2" credit-placement="edge" />
    </div>
</x-vdbs.layout.grid>
<p><code>below</code> eignet sich für Bilder im Text, <code>edge</code> für große Bilder (Hero, Teaser), bei denen eine Zeile darunter stören würde. Auf dem Handy wird <code>edge</code> zu einem waagerechten Streifen unten im Bild.</p>

<h2>Lange Quellen (Overflow)</h2>
<x-vdbs.layout.grid :columns="2">
    <div>
        <h3><code>credit-overflow="wrap"</code> (Standard)</h3>
        <x-vdbs.media.image :src="placeholder('teaser', 2)" alt="" ratio="3-2" credit="Travel With Lenses mit einem sehr langen Namen, Pexels" credit-placement="edge" />
    </div>
    <div>
        <h3><code>credit-overflow="truncate"</code></h3>
        <x-vdbs.media.image :src="placeholder('teaser', 2)" alt="" ratio="3-2" credit="Travel With Lenses mit einem sehr langen Namen, Pexels" credit-placement="edge" credit-overflow="truncate" />
    </div>
</x-vdbs.layout.grid>
<p>Zu lange Quellen brechen standardmäßig in eine zweite Spalte um und bleiben im Bild. Mit <code>truncate</code> werden sie stattdessen einzeilig gekürzt.</p>

<h2>Seitenverhältnisse</h2>
<x-vdbs.layout.grid :columns="4">
    @foreach (['16-9', '3-2', '4-3', '1-1'] as $ratio)
        <x-vdbs.media.image :src="placeholder('teaser', 2)" alt="" :ratio="$ratio" credit-placement="edge" />
    @endforeach
</x-vdbs.layout.grid>
<p>Von links: <code>16-9</code>, <code>3-2</code>, <code>4-3</code>, <code>1-1</code>. Weitere Werte: <code>21-9</code> und <code>original</code>.</p>

<h2>Fokuspunkt</h2>
<x-vdbs.layout.grid :columns="2">
    <x-vdbs.media.image :src="placeholder('hero', 4)" alt="" ratio="1-1" focus="20% 50%" credit-placement="edge" />
    <x-vdbs.media.image :src="placeholder('hero', 4)" alt="" ratio="1-1" focus="80% 50%" credit-placement="edge" />
</x-vdbs.layout.grid>
<p><code>focus="20% 50%"</code> (links) und <code>focus="80% 50%"</code> (rechts) bestimmen, welcher Ausschnitt bei engem Seitenverhältnis stehen bleibt.</p>

<h2>Regeln</h2>
<ul>
    <li><strong>Quelle:</strong> Entweder steht das Bild im Manifest (<code>sources.json</code> im Bildordner, Suche über den Dateinamen) oder die Quelle kommt als <code>credit="…"</code> am Bild. Beides zusammen: <code>credit</code> gewinnt.</li>
    <li><strong>Fehlt die Quelle</strong> oder der Alt-Text, gibt es außerhalb der Produktion eine Exception. In Produktion erscheint stattdessen „Bildquelle: fehlt“ und es wird ein Fehler protokolliert (<code>config/images.php</code>, <code>IMAGES_STRICT</code>).</li>
    <li><strong>Alt-Text:</strong> Pflicht. <code>alt=""</code> markiert ein Bild bewusst als dekorativ.</li>
    <li><strong>Platzhalterbilder</strong> (<code>placeholder('hero')</code>) gibt es nur in der Entwicklung, ein Test verhindert ihre Verwendung außerhalb der Design-Bibliothek.</li>
</ul>

<h2>Manifest der Platzhalterbilder</h2>
<x-vdbs.data.table caption="Bildquellen im Manifest" :columns="[
    ['key' => 'file', 'label' => 'Datei'],
    ['key' => 'label', 'label' => 'Quelle'],
    ['key' => 'license', 'label' => 'Lizenz'],
]">
    @foreach ($manifest as $file => $source)
        <tr>
            <th scope="row"><code>{{ $file }}</code></th>
            <td>@if ($source->url) <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer">{{ $source->label() }}</a> @else {{ $source->label() }} @endif</td>
            <td>{{ $source->license }}</td>
        </tr>
    @endforeach
</x-vdbs.data.table>

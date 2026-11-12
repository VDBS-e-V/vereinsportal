@php
    $overview = \App\Modules\DesignLibrary\Support\PlaceholderImages::make()->overview();
@endphp

<p>Eigene Bilder in <code>public/images/placeholder/</code> ablegen. Namensschema: <code>&lt;art&gt;-&lt;nn&gt;.&lt;endung&gt;</code>, zum Beispiel <code>hero-01.jpg</code>.</p>

<x-vdbs.data.table caption="Arten von Platzhalterbildern" :columns="[
    ['key' => 'kind', 'label' => 'Art'],
    ['key' => 'ratio', 'label' => 'Format'],
    ['key' => 'size', 'label' => 'Größe'],
    ['key' => 'use', 'label' => 'Verwendung'],
    ['key' => 'files', 'label' => 'Hochgeladen'],
]">
    @foreach ($overview as $row)
        <tr>
            <th scope="row"><code>{{ $row['kind'] }}-nn</code></th>
            <td>{{ $row['ratio'] }}</td>
            <td>{{ $row['size'] }}</td>
            <td>{{ $row['use'] }}</td>
            <td>
                @if (count($row['files']))
                    <x-vdbs.data.badge tone="success">{{ count($row['files']) }}</x-vdbs.data.badge>
                @else
                    <x-vdbs.data.badge>Fallback</x-vdbs.data.badge>
                @endif
            </td>
        </tr>
    @endforeach
</x-vdbs.data.table>

<h2>Verwendung im Code</h2>
<pre><code>placeholder('hero')        // erstes Hero-Bild
placeholder('teaser', 2)   // zweites Teaser-Bild (springt zurück an den Anfang, wenn es weniger gibt)</code></pre>
<p>Ohne eigene Bilder greift der Fallback <code>&lt;art&gt;.svg</code>. Erlaubte Endungen: jpg, jpeg, png, webp, avif, svg.</p>
<p>Bestand als JSON: <a href="{{ route('design-library.placeholders') }}"><code>/design/api/platzhalter</code></a></p>

<h2>Vorschau</h2>
@foreach ($overview as $row)
    <h3>{{ $row['kind'] }}</h3>
    <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);margin-bottom:var(--space-4)">
        @forelse ($row['urls'] as $url)
            <figure style="margin:0;width:12rem">
                <img src="{{ $url }}" alt="" style="width:100%;height:auto;display:block">
                <figcaption style="font-size:var(--font-size-caption)">{{ basename($url) }}</figcaption>
            </figure>
        @empty
            <figure style="margin:0;width:12rem">
                <img src="{{ $row['fallback'] }}" alt="" style="width:100%;height:auto;display:block">
                <figcaption style="font-size:var(--font-size-caption)">Fallback ({{ $row['kind'] }}.svg)</figcaption>
            </figure>
        @endforelse
    </div>
@endforeach

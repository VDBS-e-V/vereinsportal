@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Briefvorlagen', 'href' => route('letters.index')], ['label' => 'Platzhalter']];
@endphp

<x-layouts.app title="Platzhalter" width="wide">
    <x-vdbs.layout.page-header title="Platzhalter für Word-Vorlagen" lead="Schreiben Sie den Platzhalter genau so in Word, mit Dollarzeichen und geschweiften Klammern, zum Beispiel ${datum}. Das Portal ersetzt ihn beim Erzeugen durch den echten Wert." :breadcrumb="$crumbs" />

    <p><x-vdbs.actions.button variant="secondary" :href="route('letters.variables-file')" icon="download">Liste als Word-Datei</x-vdbs.actions.button></p>

    <x-vdbs.feedback.alert variant="info"><strong>Tipps für Word:</strong> Tippen Sie jeden Platzhalter in einem Zug, ohne zwischendurch zu formatieren, zu löschen oder die Rechtschreibkorrektur zu bestätigen. Wenn ein Platzhalter nicht gefüllt wird, markieren Sie ihn, löschen Sie ihn und tippen Sie ihn neu. Am sichersten ist es, ihn aus dieser Seite zu kopieren und mit „Nur Text einfügen“ einzusetzen. Die Formatierung (Schriftart, fett) bestimmt, wie Sie den Platzhalter in Word formatieren.</x-vdbs.feedback.alert>

    <section style="margin-top:var(--space-6)">
        <h2 style="font-size:var(--font-size-h3)">Wenn-Abfragen: Textblöcke nur unter einer Bedingung</h2>
        <p>Mit zwei Markierungen blenden Sie Text ein oder aus. Jede Markierung steht in Word <strong>allein in einem eigenen Absatz</strong>. Eine Bedingung gilt als erfüllt, wenn der Platzhalter einen Wert hat.</p>
        <ul>
            <li><code>${wenn_NAME}</code> … <code>${/wenn_NAME}</code>: zeigt den Inhalt, wenn NAME einen Wert hat.</li>
            <li><code>${ohne_NAME}</code> … <code>${/ohne_NAME}</code>: zeigt den Inhalt, wenn NAME leer ist.</li>
            <li>Ein „sonst“ gibt es nicht: Setzen Sie einen <code>wenn</code>- und einen <code>ohne</code>-Block mit demselben Namen hintereinander.</li>
            <li>Für NAME ist jeder Platzhalter aus den Tabellen unten möglich. Besonders nützlich sind die mit „Oft leer“ oder „Leer, wenn …“ im Hinweis.</li>
            <li>Gleich benannte Blöcke dürfen mehrfach vorkommen. Blöcke ineinander zu schachteln geht nur mit verschiedenen Namen.</li>
            <li>Ein Block mit unbekanntem NAME bleibt sichtbar stehen. So erkennen Sie Tippfehler im Namen.</li>
            <li>Die Markierungen funktionieren im Fließtext, nicht in Tabellen, Textfeldern oder Kopf- und Fußzeilen.</li>
        </ul>

        <h3 style="font-size:var(--font-size-h4);margin-top:var(--space-5)">Fertige Beispiele zum Kopieren</h3>
        @foreach ($recipes as $recipe)
            <div style="margin-top:var(--space-4)">
                <strong>{{ $recipe['title'] }}</strong>
                <p style="margin:var(--space-1) 0">{{ $recipe['purpose'] }}</p>
                <pre style="white-space:pre-wrap">{{ implode("\n", $recipe['lines']) }}</pre>
            </div>
        @endforeach
    </section>

    @foreach ($groups as $group)
        <section style="margin-top:var(--space-6)">
            <h2 style="font-size:var(--font-size-h3)">{{ $group['label'] }}</h2>
            <p>{{ $group['intro'] }}</p>
            <x-vdbs.data.table :caption="$group['label']" :columns="[['key' => 'name', 'label' => 'Platzhalter'], ['key' => 'meaning', 'label' => 'Bedeutung'], ['key' => 'example', 'label' => 'Beispiel'], ['key' => 'source', 'label' => 'Quelle und Hinweis']]">
                @foreach ($group['items'] as $name => $item)
                    <tr>
                        <td><code>${{ '{'.$name.'}' }}</code></td>
                        <td>{{ $item['meaning'] }}</td>
                        <td style="white-space:pre-line">{{ $item['example'] }}</td>
                        <td>{{ $item['source'] }}@if ($item['note'] !== '')<br><small>{{ $item['note'] }}</small>@endif</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endforeach
</x-layouts.app>

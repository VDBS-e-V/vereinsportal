@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Briefvorlagen', 'href' => route('letters.index')], ['label' => 'Platzhalter']];
@endphp

<x-layouts.app title="Platzhalter" width="wide">
    <x-vdbs.layout.page-header title="Platzhalter für Word-Vorlagen" lead="Schreiben Sie den Platzhalter genau so in Word, mit Dollarzeichen und geschweiften Klammern, zum Beispiel ${datum}. Das Portal ersetzt ihn beim Erzeugen durch den echten Wert." :breadcrumb="$crumbs" />

    <p><x-vdbs.actions.button variant="secondary" :href="route('letters.variables-file')" icon="download">Liste als Word-Datei</x-vdbs.actions.button></p>

    <x-vdbs.feedback.alert variant="info"><strong>Tipps für Word:</strong> Tippen Sie jeden Platzhalter in einem Zug, ohne zwischendurch zu formatieren, zu löschen oder die Rechtschreibkorrektur zu bestätigen. Wenn ein Platzhalter nicht gefüllt wird, markieren Sie ihn, löschen Sie ihn und tippen Sie ihn neu. Am sichersten ist es, ihn aus dieser Seite zu kopieren und mit „Nur Text einfügen“ einzusetzen. Die Formatierung (Schriftart, fett) bestimmt, wie Sie den Platzhalter in Word formatieren.</x-vdbs.feedback.alert>

    <section style="margin-top:var(--space-6)">
        <h2 style="font-size:var(--font-size-h3)">Wenn-Abfragen: Textblöcke nur unter einer Bedingung</h2>
        <p>Mit zwei Markierungen blenden Sie Text ein oder aus. Jede Markierung steht in Word **allein in einem eigenen Absatz**:</p>
        <pre style="white-space:pre-wrap">${wenn_ansprechperson}
Ihre Ansprechperson: ${ansprechperson}
${/wenn_ansprechperson}</pre>
        <p>Der Block „Ihre Ansprechperson …“ erscheint nur, wenn es eine Ansprechperson gibt. Das Gegenstück ist <code>${ohne_…}</code>:</p>
        <pre style="white-space:pre-wrap">${ohne_ansprechperson}
Bitte melden Sie sich bei der Geschäftsstelle.
${/ohne_ansprechperson}</pre>
        <ul>
            <li><code>${wenn_NAME}</code> … <code>${/wenn_NAME}</code>: zeigt den Inhalt, wenn der Platzhalter NAME einen Wert hat (zum Beispiel <code>wenn_verguetung</code>, <code>wenn_seminar_frist</code>, <code>wenn_freitext</code>).</li>
            <li><code>${ohne_NAME}</code> … <code>${/ohne_NAME}</code>: zeigt den Inhalt, wenn NAME leer ist.</li>
            <li>Für verschiedene Texte an Schulen und Personen: <code>${wenn_empfaenger_ist_schule}</code> und <code>${wenn_empfaenger_ist_person}</code>. So reicht eine Vorlage für beide.</li>
            <li>Blöcke mit unbekanntem NAME bleiben sichtbar stehen. Gleich benannte Blöcke dürfen mehrfach vorkommen. Blöcke ineinander zu schachteln ist möglich, wenn die Namen verschieden sind.</li>
        </ul>
    </section>

    @foreach ($groups as $group)
        <section style="margin-top:var(--space-6)">
            <h2 style="font-size:var(--font-size-h3)">{{ $group['label'] }}</h2>
            <x-vdbs.data.table :caption="$group['label']" :columns="[['key' => 'name', 'label' => 'Platzhalter'], ['key' => 'meaning', 'label' => 'Bedeutung']]">
                @foreach ($group['items'] as $name => $meaning)
                    <tr><td><code>${{ '{'.$name.'}' }}</code></td><td>{{ $meaning }}</td></tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endforeach
</x-layouts.app>

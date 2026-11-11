@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Briefvorlagen', 'href' => route('letters.index')], ['label' => 'Platzhalter']];
@endphp

<x-layouts.app title="Platzhalter" width="wide">
    <x-vdbs.layout.page-header title="Platzhalter für Word-Vorlagen" lead="Schreiben Sie den Platzhalter genau so in Word, mit Dollarzeichen und geschweiften Klammern, zum Beispiel ${datum}. Das Portal ersetzt ihn beim Erzeugen durch den echten Wert." :breadcrumb="$crumbs" />

    <x-vdbs.feedback.alert variant="info"><strong>Tipps für Word:</strong> Tippen Sie jeden Platzhalter in einem Zug, ohne zwischendurch zu formatieren, zu löschen oder die Rechtschreibkorrektur zu bestätigen. Wenn ein Platzhalter nicht gefüllt wird, markieren Sie ihn, löschen Sie ihn und tippen Sie ihn neu. Am sichersten ist es, ihn aus dieser Seite zu kopieren und mit „Nur Text einfügen“ einzusetzen. Die Formatierung (Schriftart, fett) bestimmt, wie Sie den Platzhalter in Word formatieren.</x-vdbs.feedback.alert>

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

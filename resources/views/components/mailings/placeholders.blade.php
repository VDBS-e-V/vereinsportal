@props(['placeholders'])

<details class="vdbs-mailings__placeholders">
    <summary>Platzhalter anzeigen ({{ count($placeholders) }})</summary>
    <p>Schreiben Sie den Platzhalter samt geschweiften Klammern in Betreff oder Text. Beim Senden wird er für jede Person ersetzt.</p>
    <x-vdbs.data.table caption="Platzhalter" :zebra="false" :columns="[['key' => 'key', 'label' => 'Platzhalter'], ['key' => 'meaning', 'label' => 'Bedeutung']]">
        @foreach ($placeholders as $key => $meaning)
            <tr>
                <td><code>{{ '{'.$key.'}' }}</code></td>
                <td>{{ $meaning }}</td>
            </tr>
        @endforeach
    </x-vdbs.data.table>
</details>

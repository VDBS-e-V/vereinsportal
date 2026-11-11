@props(['placeholders'])

<details class="vdbs-mailings__placeholders">
    <summary>Platzhalter anzeigen ({{ count($placeholders) }})</summary>
    <p>Schreiben Sie den Platzhalter samt geschweiften Klammern in Betreff oder Text. Beim Senden wird er für jede Person ersetzt. Mit <code>{name|Standardwert}</code> legen Sie fest, was stehen soll, wenn der Wert fehlt, zum Beispiel <code>{ort|noch offen}</code>.</p>
    <x-vdbs.data.table caption="Platzhalter" :zebra="false" :columns="[['key' => 'key', 'label' => 'Platzhalter'], ['key' => 'meaning', 'label' => 'Bedeutung']]">
        @foreach ($placeholders as $key => $meaning)
            <tr>
                <td><code>{{ '{'.$key.'}' }}</code> <x-vdbs.actions.button type="button" size="s" variant="text" style="display:none" :data-insert-placeholder="'{'.$key.'}'">Einfügen</x-vdbs.actions.button></td>
                <td>{{ $meaning }}</td>
            </tr>
        @endforeach
    </x-vdbs.data.table>
</details>

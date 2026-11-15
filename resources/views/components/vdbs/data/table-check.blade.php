@props(['name', 'value' => '1', 'label', 'form' => null, 'checked' => false])

{{--
    Auswahlkästchen in einer Tabellenzeile (Paket "data"): eine Zelle <td> mit dem Kästchen des Designsystems.
    name/value  wie bei einer Checkbox (Zeilenauswahl: name="ids[]" value="<id>")
    label       Text für Screenreader, z. B. "Mitgliedschaft auswählen" (das Kästchen hat keine sichtbare Beschriftung)
    form        id des Formulars, zu dem das Kästchen gehört; zusammen mit ['selectAll' => '<id>'] in den Spalten der
                Tabelle wählt das Kopfkästchen alle Zeilen (resources/js/select-all.js)
    checked     Standardzustand
--}}
<td class="vdbs-table__select">
    <span class="vdbs-choice vdbs-choice--checkbox">
        <input class="vdbs-choice__input" type="checkbox" name="{{ $name }}" value="{{ $value }}" aria-label="{{ $label }}" @if ($form) form="{{ $form }}" @endif @checked($checked) {{ $attributes }}>
    </span>
</td>

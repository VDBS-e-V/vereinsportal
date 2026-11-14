@props(['i', 'row' => []])

{{-- Eine Zeile „Frage und Antwort“ im FAQ-Formular. $i ist der Index (oder __INDEX__ in der Vorlage für „Neue hinzufügen“). --}}
<fieldset data-vdbs-repeater-row style="border:0;padding:0;margin:0 0 var(--space-5);border-top:1px solid var(--border-default);padding-top:var(--space-4)">
    <legend class="vdbs-sr-only">Frage und Antwort</legend>
    <x-vdbs.forms.input :id="'entry-'.$i.'-question'" :name="'entries['.$i.'][question]'" label="Frage" :optional="true" maxlength="250" :value="$row['question'] ?? null" />
    <x-vdbs.forms.textarea :id="'entry-'.$i.'-answer'" :name="'entries['.$i.'][answer]'" label="Antwort" :optional="true" :rows="4" maxlength="5000" :value="$row['answer'] ?? null" />
    <x-vdbs.actions.button type="button" variant="text" size="s" class="vdbs-js-only" data-vdbs-repeater-remove>Zeile entfernen</x-vdbs.actions.button>
</fieldset>

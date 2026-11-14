@props(['i', 'row' => []])

{{-- Eine Zeile „Eigenes Feld“ im Formularformular. $i ist der Index (oder __INDEX__ in der Vorlage für „Neue hinzufügen“). --}}
@php
    $options = $row['options'] ?? '';
    $options = is_array($options) ? implode("\n", $options) : (string) $options;
@endphp
<fieldset data-vdbs-repeater-row style="border:0;padding:0;margin:0 0 var(--space-5);border-top:1px solid var(--border-default);padding-top:var(--space-4)">
    <legend class="vdbs-sr-only">Eigenes Feld</legend>
    <input type="hidden" name="fields[{{ $i }}][key]" value="{{ $row['key'] ?? '' }}">
    <x-vdbs.forms.form-grid :columns="2">
        <x-vdbs.forms.input :id="'field-'.$i.'-label'" :name="'fields['.$i.'][label]'" label="Bezeichnung" :optional="true" maxlength="120" :value="$row['label'] ?? null" hint="So steht die Frage im Formular." />
        <x-vdbs.forms.select :id="'field-'.$i.'-type'" :name="'fields['.$i.'][type]'" label="Art des Feldes" :options="\App\Modules\ContactForms\Support\ContactFormFields::TYPES" :value="$row['type'] ?? 'text'" />
    </x-vdbs.forms.form-grid>
    <x-vdbs.forms.textarea :id="'field-'.$i.'-options'" :name="'fields['.$i.'][options]'" label="Einträge der Auswahl" :optional="true" :rows="3" maxlength="3000" :value="$options" hint="Nur bei „Auswahl aus einer Liste“: ein Eintrag je Zeile." />
    <x-vdbs.forms.input :id="'field-'.$i.'-hint'" :name="'fields['.$i.'][hint]'" label="Hinweis unter dem Feld" :optional="true" maxlength="200" :value="$row['hint'] ?? null" />
    <div style="display:flex;gap:var(--space-5);align-items:center;flex-wrap:wrap">
        <x-vdbs.forms.checkbox :id="'field-'.$i.'-required'" :name="'fields['.$i.'][required]'" label="Pflichtfeld" :checked="filter_var($row['required'] ?? false, FILTER_VALIDATE_BOOLEAN)" />
        <x-vdbs.actions.button type="button" variant="text" size="s" class="vdbs-js-only" data-vdbs-repeater-remove>Feld entfernen</x-vdbs.actions.button>
    </div>
</fieldset>

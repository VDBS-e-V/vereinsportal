<x-vdbs.forms.input name="name" label="Name der Vorlage" :required="true" maxlength="120" :value="old('name', $template?->name)" />
<x-vdbs.forms.input name="title" label="Titel der Aufgabe" :required="true" maxlength="200" :value="old('title', $template?->title)" />
<x-vdbs.forms.textarea name="description" label="Beschreibung" :optional="true" maxlength="5000" :rows="4" :value="old('description', $template?->description)" />
<x-vdbs.forms.select name="priority" label="Priorität" :required="true" :options="$priorities" :value="old('priority', $template?->priority ?? 'normal')" />
<x-vdbs.forms.input name="due_in_days" label="Fällig nach (Tagen)" type="number" :optional="true" min="0" max="3650" :value="old('due_in_days', $template?->due_in_days)" hint="Leer = keine Fälligkeit. Gezählt ab dem Tag, an dem die Aufgabe angelegt wird." />
@if ($labels !== [])
    <x-vdbs.forms.multi-select name="labels" label="Labels" :optional="true" placeholder="Kein Label ausgewählt" :options="$labels" :value="old('labels', $template ? $template->labels->pluck('id')->all() : [])" />
@endif
<x-vdbs.forms.textarea name="subtasks" label="Unteraufgaben" :optional="true" maxlength="5000" :rows="5" :value="old('subtasks', $template?->subtasks)" hint="Eine Zeile pro Unteraufgabe." />

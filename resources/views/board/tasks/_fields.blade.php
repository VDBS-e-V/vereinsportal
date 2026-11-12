<x-vdbs.forms.input name="title" label="Aufgabe" :required="true" maxlength="200" :value="old('title', $task?->title)" />
<x-vdbs.forms.textarea name="notes" label="Hinweise" :optional="true" maxlength="2000" :rows="3" :value="old('notes', $task?->notes)" />
<x-vdbs.forms.multi-select name="assignees" label="Zuständig" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$members" :value="old('assignees', $task ? $task->assignees->pluck('id')->all() : [])" hint="Mehrere Personen möglich." />
<x-vdbs.forms.input name="due_on" label="Fällig am" type="date" :optional="true" :value="old('due_on', $task?->due_on?->format('Y-m-d'))" />
@if ($resolutions !== [])
    <x-vdbs.forms.select name="board_resolution_id" label="Zu Beschluss" :optional="true" placeholder="Kein Beschluss" :options="$resolutions" :value="old('board_resolution_id', $task?->board_resolution_id)" />
@endif

<x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="120" :value="old('name', $project?->name)" />
<x-vdbs.forms.textarea name="description" label="Beschreibung" :optional="true" maxlength="2000" :rows="3" :value="old('description', $project?->description)" />
@if ($roleEditable)
    <x-vdbs.forms.select name="role_key" label="Automatisch alle mit dieser Rolle" :optional="true" placeholder="Keine (nur eingetragene Mitglieder)" :options="$roles" :value="old('role_key', $project?->role_key)" hint="Alle Konten mit dieser Rolle sind Mitglieder und dürfen das Projekt verwalten." />
@endif
<x-vdbs.forms.multi-select name="managers" label="Verwaltende" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$users" :value="old('managers', $managers)" hint="Dürfen Spalten und Mitglieder ändern und fremde Aufgaben löschen." />
<x-vdbs.forms.multi-select name="members" label="Mitglieder" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$users" :value="old('members', $members)" hint="Dürfen Aufgaben anlegen, bearbeiten und verschieben." />

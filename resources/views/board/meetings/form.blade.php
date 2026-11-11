@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Sitzungen', 'href' => route('board.meetings.index')], ['label' => $meeting ? 'Bearbeiten' : 'Neue Sitzung']];
@endphp

<x-layouts.app :title="$meeting ? 'Sitzung bearbeiten' : 'Neue Sitzung'" width="wide">
    <x-vdbs.layout.page-header :title="$meeting ? 'Sitzung bearbeiten' : 'Neue Sitzung'" lead="Termin, Tagesordnung, Anwesenheit und Protokoll. Tagesordnung und Protokoll können Sie auch später ergänzen." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$meeting ? route('board.meetings.update', $meeting) : route('board.meetings.store')" :method="$meeting ? 'put' : 'post'">
        <x-vdbs.forms.input name="title" label="Titel" :required="true" maxlength="150" :value="old('title', $meeting?->title ?? 'Vorstandssitzung')" />
        <x-vdbs.forms.input name="date" label="Datum" type="date" :required="true" :value="old('date', $meeting?->starts_at->format('Y-m-d'))" />
        <x-vdbs.forms.input name="start_time" label="Beginn" type="time" :required="true" :value="old('start_time', $meeting?->starts_at->format('H:i') ?? '19:00')" />
        <x-vdbs.forms.input name="end_time" label="Ende" type="time" :required="true" :value="old('end_time', $meeting?->ends_at->format('H:i') ?? '21:00')" />
        <x-vdbs.forms.input name="location" label="Ort" :optional="true" maxlength="200" :value="old('location', $meeting?->location)" hint="Zum Beispiel Geschäftsstelle oder „online“." />
        <x-vdbs.forms.input name="online_link" label="Link zur Videokonferenz" type="url" :optional="true" maxlength="500" :value="old('online_link', $meeting?->online_link)" hint="Beginnt mit https://. Erscheint im Kalendereintrag." />
        <x-vdbs.forms.select name="status" label="Stand" :required="true" :options="$statuses" :value="old('status', $meeting?->status ?? 'planned')" />
        <x-vdbs.forms.textarea name="agenda" label="Tagesordnung" :optional="true" maxlength="60000" :rows="10" :rich="true" :value="old('agenda', $agenda)" />
        <x-vdbs.forms.multi-select name="present" label="Anwesend" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$members" :value="old('present', $present)" />
        <x-vdbs.forms.multi-select name="excused" label="Entschuldigt" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$members" :value="old('excused', $excused)" />
        <x-vdbs.forms.textarea name="minutes" label="Protokoll" :optional="true" maxlength="100000" :rows="16" :rich="true" :value="old('minutes', $minutes)" hint="Beschlüsse, Ergebnisse und Aufgaben. Wird das Protokoll nach der Genehmigung geändert, ist es wieder nicht genehmigt." />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="$meeting ? route('board.meetings.show', $meeting) : route('board.meetings.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

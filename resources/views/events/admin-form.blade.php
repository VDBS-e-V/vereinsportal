@php
    $editing = $event !== null;
    $heading = $editing ? 'Veranstaltung bearbeiten' : 'Veranstaltung anlegen';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Veranstaltungen', 'href' => route('events.admin.index')], ['label' => $editing ? $event->title : 'Neu']];
@endphp

<x-layouts.app :title="$heading" width="medium">
    <x-vdbs.layout.page-header :title="$heading" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('events.admin.update', $event) : route('events.admin.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.input name="title" label="Titel" :required="true" maxlength="200" :value="$values['title'] ?? null" />
        <x-vdbs.forms.textarea name="description" label="Beschreibung" :optional="true" maxlength="5000" :rows="6" :value="$values['description'] ?? null" hint="Wird öffentlich auf der Anmeldeseite angezeigt" />
        <x-vdbs.forms.form-grid :columns="2">
            <x-vdbs.forms.input name="location" label="Ort" :optional="true" maxlength="200" :value="$values['location'] ?? null" />
            <x-vdbs.forms.input name="capacity" label="Plätze" type="number" min="1" max="5000" :optional="true" :value="$values['capacity'] ?? null" hint="Leer = unbegrenzt" />
            <x-vdbs.forms.select name="status" label="Status" :required="true" :options="\App\Modules\Events\Enums\EventStatus::options()" :value="$values['status'] ?? 'draft'" hint="Nur „Anmeldung offen“ erlaubt Anmeldungen; Entwürfe sehen nur Sie" />
            <x-vdbs.forms.input name="registration_deadline" label="Anmeldeschluss" type="datetime-local" :optional="true" :value="$values['registration_deadline'] ?? null" />
        </x-vdbs.forms.form-grid>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="$editing ? route('events.admin.show', $event) : route('events.admin.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

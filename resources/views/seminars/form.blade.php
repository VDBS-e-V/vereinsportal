@php
    $editing = $seminar !== null;
    $heading = $editing ? 'Seminar bearbeiten' : 'Seminar anlegen';
    $crumbs = array_values(array_filter([
        ['label' => 'Start', 'href' => route('home')],
        ['label' => 'Koordination'],
        ['label' => 'Seminare', 'href' => route('seminars.index')],
        $editing ? ['label' => $seminar->title, 'href' => route('seminars.show', $seminar)] : null,
        ['label' => $editing ? 'Bearbeiten' : 'Anlegen'],
    ]));
@endphp

<x-layouts.app :title="$heading" width="medium">
    <x-vdbs.layout.page-header :title="$heading" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('seminars.update', $seminar) : route('seminars.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Seminar">
            <x-vdbs.forms.input name="title" label="Titel" :required="true" :value="$values['title'] ?? null" hint="z. B. SV-Tage Herbst 2026" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="type" label="Leistung" :required="true" :options="$typeOptions" :value="$values['type'] ?? null" hint="Bestimmt die Voreinstellungen im Kostenrechner" />
                <x-vdbs.forms.select name="partner_id" label="Schule" placeholder="Bitte wählen" :required="true" :options="$partnerOptions" :value="$values['partner_id'] ?? null" />
                <x-vdbs.forms.input name="grade_level" label="Klassenstufe" :optional="true" :value="$values['grade_level'] ?? null" hint="z. B. Klasse 7 bis 10" />
                <x-vdbs.forms.input name="expected_participants" label="Erwartete Teilnehmende" type="number" min="1" max="2000" :optional="true" :value="$values['expected_participants'] ?? null" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Notizen">
            <x-vdbs.forms.textarea name="notes" label="Notiz" :optional="true" maxlength="2000" :counter="true" :rows="4" :value="$values['notes'] ?? null" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">{{ $editing ? 'Speichern' : 'Seminar anlegen' }}</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="$editing ? route('seminars.show', $seminar) : route('seminars.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

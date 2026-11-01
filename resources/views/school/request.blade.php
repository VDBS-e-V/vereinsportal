@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen', 'href' => route('school.home')], ['label' => 'Seminar anfragen']];
@endphp

<x-layouts.app title="Seminar anfragen" width="medium">
    <x-vdbs.layout.page-header title="Seminar anfragen" lead="Sagen Sie uns, was Ihre Schule braucht. Die Koordination meldet sich mit einem Vorschlag und einem Kostenvoranschlag." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('school.requests.store')">
        <x-vdbs.forms.select name="type" label="Was möchten Sie anfragen?" :required="true" placeholder="Bitte wählen" :options="$types" />
        <x-vdbs.forms.form-grid :columns="2">
            <x-vdbs.forms.input name="wished_period" label="Wunschzeitraum" :optional="true" maxlength="200" hint="z. B. „Februar oder März“ oder „vor den Sommerferien“" />
            <x-vdbs.forms.input name="expected_participants" label="Erwartete Teilnehmende" type="number" min="1" max="2000" :optional="true" />
            <x-vdbs.forms.input name="grade_level" label="Klassenstufe" :optional="true" maxlength="100" />
        </x-vdbs.forms.form-grid>
        <x-vdbs.forms.textarea name="notes" label="Weitere Hinweise" :optional="true" :rows="6" :rich="true" />

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Anfrage senden</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('school.home')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

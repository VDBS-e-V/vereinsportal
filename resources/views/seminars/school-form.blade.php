@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen', 'href' => route('school.home')], ['label' => 'Seminare', 'href' => route('school.seminars.index')], ['label' => 'Anfrage']];
@endphp

<x-layouts.app title="Seminar anfragen" width="wide">
    <x-vdbs.layout.page-header title="Seminar anfragen" lead="Beschreiben Sie, was Sie sich wünschen. Die Geschäftsstelle prüft Ihre Anfrage und schickt Ihnen einen Kostenvoranschlag." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('school.seminars.store')">
        <x-vdbs.forms.form-section title="Ihr Wunsch">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="title" label="Thema oder Titel" :required="true" maxlength="200" :value="old('title')" />
                <x-vdbs.forms.input name="format" label="Format" :optional="true" maxlength="120" :value="old('format')" hint="Zum Beispiel Projekttag, Workshop oder Reihe." />
                <x-vdbs.forms.input name="participants" label="Anzahl Teilnehmende" type="number" :optional="true" :value="old('participants')" />
                <x-vdbs.forms.input name="desired_period" label="Gewünschter Zeitraum" :optional="true" maxlength="200" :value="old('desired_period')" hint="Zum Beispiel „März 2027“ oder „vor den Sommerferien“." />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.textarea name="request_note" label="Anmerkungen" :optional="true" maxlength="3000" :value="old('request_note')" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Ansprechperson an der Schule">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="contact_name" label="Name" :required="true" maxlength="150" :value="old('contact_name', $defaults['contact_name'])" />
                <x-vdbs.forms.input name="contact_email" label="E-Mail-Adresse" type="email" :required="true" maxlength="254" :value="old('contact_email', $defaults['contact_email'])" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="check">Anfrage senden</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('school.seminars.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

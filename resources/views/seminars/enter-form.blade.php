@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Seminare'], ['label' => 'Anfrage eintragen']];
@endphp

<x-layouts.app title="Seminaranfrage eintragen" width="wide">
    <x-vdbs.layout.page-header title="Seminaranfrage eintragen" lead="Tragen Sie eine Anfrage ein, die Sie telefonisch oder schriftlich von einer Schule bekommen haben. Die Schule erhält dazu keine automatische Bestätigung; die Geschäftsstelle prüft die Anfrage wie jede andere." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('seminars.enter.store')">
        <x-vdbs.forms.form-section title="Schule">
            <x-vdbs.forms.select name="partner_id" label="Schule" placeholder="Schule wählen" :required="true" :options="$schools" :value="old('partner_id')" hint="Fehlt die Schule, legen Sie sie zuerst unter „Schulen“ an." />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Wunsch der Schule">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="title" label="Thema oder Titel" :required="true" maxlength="200" :value="old('title')" />
                <x-vdbs.forms.input name="format" label="Format" :optional="true" maxlength="120" :value="old('format')" hint="Zum Beispiel Projekttag, Workshop oder Reihe." />
                <x-vdbs.forms.input name="participants" label="Anzahl Teilnehmende" type="number" :optional="true" :value="old('participants')" />
                <x-vdbs.forms.input name="desired_period" label="Gewünschter Zeitraum" :optional="true" maxlength="200" :value="old('desired_period')" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.textarea name="request_note" label="Anmerkungen" :optional="true" maxlength="3000" :value="old('request_note')" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Ansprechperson an der Schule" description="Leer lassen, dann nimmt das Portal die hinterlegte Ansprechperson der Schule. Der Kostenvoranschlag geht an diese Adresse.">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="contact_name" label="Name" :optional="true" maxlength="150" :value="old('contact_name')" />
                <x-vdbs.forms.input name="contact_email" label="E-Mail-Adresse" type="email" :optional="true" maxlength="254" :value="old('contact_email')" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="check">Anfrage eintragen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('home')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

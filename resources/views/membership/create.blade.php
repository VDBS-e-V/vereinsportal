@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Mitgliedschaften', 'href' => route('membership.index')], ['label' => 'Anlegen']];
@endphp

<x-layouts.app title="Mitgliedschaft anlegen" width="medium">
    <x-vdbs.layout.page-header title="Mitgliedschaft anlegen" lead="Erfassen Sie einen Antrag oder nehmen Sie eine Person direkt auf. Die Person muss schon im Portal als Person angelegt sein." :breadcrumb="$crumbs" />

    @if ($personOptions === [])
        <x-vdbs.feedback.alert variant="info">Alle erfassten Personen haben bereits eine beantragte oder laufende Mitgliedschaft. Legen Sie zuerst unter Personen eine neue Person an.</x-vdbs.feedback.alert>
    @endif

    <x-vdbs.forms.form :action="route('membership.store')">
        <x-vdbs.forms.form-section title="Person und Art">
            <x-vdbs.forms.select name="person_id" label="Person" placeholder="Bitte wählen" :required="true" :options="$personOptions" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="mode" label="Art" :required="true" :options="['request' => 'Antrag erfassen', 'admit' => 'Direkt aufnehmen']" value="request" />
                <x-vdbs.forms.input name="starts_on" label="Beitrittsdatum" type="date" :optional="true" hint="Nur bei direkter Aufnahme" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.textarea name="comment" label="Notiz" :optional="true" maxlength="500" :counter="true" :rows="3" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Anlegen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('membership.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

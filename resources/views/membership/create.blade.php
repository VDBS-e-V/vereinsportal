@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Mitgliedschaften', 'href' => route('membership.index')], ['label' => 'Erfassen']];
@endphp

<x-layouts.app title="Mitgliedschaft erfassen" width="medium">
    <x-vdbs.layout.page-header title="Mitgliedschaft erfassen" lead="Erfassen Sie einen Antrag oder eine bestehende Mitgliedschaft mit dem Dokument. Dafür wird keine digitale Person angelegt. Online-Anträge erscheinen automatisch in der Liste." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('membership.store')" :files="true">
        <x-vdbs.forms.form-section title="Angaben zur Person">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" autocomplete="off" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" autocomplete="off" />
                <x-vdbs.forms.input name="birth_date" label="Geburtsdatum" type="date" :optional="true" />
                <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :optional="true" hint="Nötig, um später einen Registrierungslink zu senden." autocomplete="off" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Mitgliedschaft und Dokument">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="mode" label="Art" :required="true" :options="['request' => 'Antrag (noch nicht entschieden)', 'admit' => 'Bestehende Mitgliedschaft']" value="request" />
                <x-vdbs.forms.input name="starts_on" label="Beitrittsdatum" type="date" :optional="true" hint="Pflicht bei bestehender Mitgliedschaft" />
                <x-vdbs.forms.select name="kind" label="Art des Dokuments" :required="true" :options="$documentKinds" value="application" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.file name="document" label="Dokument" accept=".pdf,.jpg,.jpeg,.png" :required="true" hint="PDF, JPEG oder PNG, höchstens 10 MB. Die Datei wird privat gespeichert." />
            <x-vdbs.forms.textarea name="comment" label="Notiz" :optional="true" maxlength="500" :counter="true" :rows="3" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Erfassen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('membership.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

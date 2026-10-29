@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Nutzer', 'href' => route('admin.users.index')], ['label' => 'Nutzer anlegen']];
@endphp

<x-layouts.app title="Nutzer anlegen" width="medium">
    <x-vdbs.layout.page-header title="Nutzer anlegen" lead="Die Person erhält eine Einladung per E-Mail und legt darüber ihr Passwort fest. Der Link ist sieben Tage gültig." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('admin.users.store')">
        <x-vdbs.forms.form-section title="Person">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="title" label="Titel" :optional="true" />
                <div></div>
                <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" autocomplete="off" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" autocomplete="off" />
                <x-vdbs.forms.input name="birth_date" label="Geburtsdatum" type="date" :required="true" />
                <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" hint="Hierhin geht die Einladung. Mit dieser Adresse meldet sich die Person an." autocomplete="off" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Rolle">
            <x-vdbs.forms.select name="role" label="Rolle von Anfang an" placeholder="Keine Rolle" :optional="true" :options="$roleOptions" hint="Weitere Rollen vergeben Sie danach auf der Detailseite." />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Konto anlegen und einladen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('admin.users.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Teamkontakte', 'href' => route('contacts.index')], ['label' => 'Adresse hinzufügen']];
@endphp

<x-layouts.app title="Adresse hinzufügen" width="medium">
    <x-vdbs.layout.page-header title="Adresse hinzufügen" lead="Eine neue Person zur Liste hinzufügen. Mehrere Adressen auf einmal geht über den Import." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('contacts.store')">
        <x-vdbs.forms.form-section title="Person">
            <x-vdbs.forms.input name="email" type="email" label="E-Mail-Adresse" :required="true" autocomplete="off" maxlength="190" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="first_name" label="Vorname" :optional="true" maxlength="100" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :optional="true" maxlength="100" />
                <x-vdbs.forms.input name="phone" type="tel" label="Telefon" :optional="true" maxlength="50" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Einwilligung und Zugang">
            <x-vdbs.forms.checkbox name="consent" label="Die Einwilligung zur E-Mail-Kommunikation liegt für diese Adresse vor." :required="true" />
            <x-vdbs.forms.checkbox name="send_access" label="Den persönlichen Zugang sofort per E-Mail senden" :checked="true" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="plus">Adresse hinzufügen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('contacts.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

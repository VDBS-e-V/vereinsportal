@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Mitglied werden']];
@endphp

<x-layouts.app title="Mitglied werden" width="medium">
    <x-vdbs.layout.page-header title="Mitglied werden" lead="Beantragen Sie die Mitgliedschaft im VDBS e. V. Wir legen dafür Ihr Konto im Portal an. Über Ihren Antrag entscheidet der Vorstand." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('join.apply.store')">
        <x-vdbs.forms.form-section title="Ihre Angaben">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" autocomplete="given-name" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" autocomplete="family-name" />
                <x-vdbs.forms.input name="birth_date" label="Geburtsdatum" type="date" :required="true" autocomplete="bday" />
                <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" hint="Daran senden wir den Bestätigungslink." autocomplete="email" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Passwort für Ihr Konto">
            <x-vdbs.forms.password name="password" label="Passwort" :new="true" :required="true" :rules="['Mindestens 12 Zeichen', 'Groß- und Kleinbuchstaben', 'Mindestens eine Ziffer']" />
            <x-vdbs.forms.password name="password_confirmation" label="Passwort wiederholen" :new="true" :required="true" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Zustimmung">
            <x-vdbs.forms.checkbox name="statute" label="Ich erkenne die Satzung des VDBS e. V. an." :required="true" />
            <x-vdbs.forms.checkbox name="privacy" label="Ich habe die Datenschutzhinweise zur Kenntnis genommen." :required="true" />
        </x-vdbs.forms.form-section>

        {{-- Köder für Programme: Menschen sehen und füllen das Feld nicht aus. --}}
        <div style="position:absolute;left:-10000px" aria-hidden="true">
            <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
        </div>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="send">Mitgliedschaft beantragen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('home')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

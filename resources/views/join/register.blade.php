@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Registrierung']];
@endphp

<x-layouts.app title="Konto registrieren" width="medium">
    <x-vdbs.layout.page-header title="Konto registrieren" lead="Legen Sie Ihr Konto im Portal an. Ihre Mitgliedschaft ist dem Verein bereits bekannt." :breadcrumb="$crumbs" />

    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Name">{{ $membership->displayName() }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="E-Mail-Adresse">{{ $membership->email }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    <x-vdbs.forms.form :action="route('join.register.store', $token)" style="margin-top:var(--space-6)">
        <x-vdbs.forms.bot-guard />
        <x-vdbs.forms.form-section title="Passwort">
            <x-vdbs.forms.password name="password" label="Passwort" :new="true" :required="true" :rules="['Mindestens 12 Zeichen', 'Groß- und Kleinbuchstaben', 'Mindestens eine Ziffer']" />
            <x-vdbs.forms.password name="password_confirmation" label="Passwort wiederholen" :new="true" :required="true" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Weitere Angaben">
            <x-vdbs.forms.form-grid :columns="2">
                @if ($membership->birth_date === null)
                    <x-vdbs.forms.input name="birth_date" label="Geburtsdatum" type="date" :required="true" autocomplete="bday" />
                @endif
                <x-vdbs.forms.input name="phone" label="Telefon" type="tel" :optional="true" autocomplete="tel" />
                <x-vdbs.forms.input name="street" label="Straße" :optional="true" autocomplete="address-line1" />
                <x-vdbs.forms.input name="house_number" label="Hausnummer" :optional="true" />
                <x-vdbs.forms.input name="postal_code" label="Postleitzahl" :optional="true" autocomplete="postal-code" />
                <x-vdbs.forms.input name="city" label="Ort" :optional="true" autocomplete="address-level2" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Zustimmung">
            <x-vdbs.forms.checkbox name="privacy" label="Ich habe die Datenschutzhinweise zur Kenntnis genommen." :required="true" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="check">Konto anlegen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

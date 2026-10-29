<x-layouts.app title="Passwort vergessen" width="narrow">
    <h1>Passwort vergessen</h1>
    <p>Geben Sie die E-Mail-Adresse Ihres Kontos ein. Wir senden Ihnen einen Link, über den Sie ein neues Passwort festlegen.</p>

    <x-vdbs.forms.form :action="route('password.email')">
        <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" autocomplete="username" autofocus />

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" :full="true" size="l">Link anfordern</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    <p style="margin-top:var(--space-5)"><x-vdbs.actions.button variant="quiet" :href="route('login')" icon="arrow-left">Zurück zur Anmeldung</x-vdbs.actions.button></p>
</x-layouts.app>

<x-layouts.app title="Neues Passwort" width="narrow">
    <h1>Neues Passwort festlegen</h1>
    <p>Wählen Sie ein neues Passwort für Ihr Konto.</p>

    <x-vdbs.forms.form :action="route('password.update')">
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <x-vdbs.forms.password name="password" label="Neues Passwort" :new="true" :required="true" :rules="['Mindestens 12 Zeichen', 'Groß- und Kleinbuchstaben', 'Mindestens eine Ziffer']" autofocus />
        <x-vdbs.forms.password name="password_confirmation" label="Neues Passwort wiederholen" :new="true" :required="true" />

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" :full="true" size="l">Passwort speichern</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>

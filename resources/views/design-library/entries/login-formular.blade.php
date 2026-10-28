<p>Muster aus <code>form</code>, <code>input</code>, <code>password</code>, <code>checkbox</code> und <code>button</code>. Schmale Seite (<code>width="narrow"</code>) für Anmeldung.</p>

<div style="max-width:var(--container-narrow)">
    <h2>Zustand: Fehler</h2>
    <x-vdbs.forms.form action="#" :summary="false">
        <x-vdbs.forms.error-summary :focus="false" title="Anmeldung nicht möglich" :errors="['email' => 'Die Anmeldedaten sind nicht korrekt oder das Konto ist nicht aktiv.']" />
        <x-vdbs.forms.input name="email_demo" label="E-Mail-Adresse" type="email" value="erika@example.org" autocomplete="username" :required="true" error="Die Anmeldedaten sind nicht korrekt oder das Konto ist nicht aktiv." />
        <x-vdbs.forms.password name="password_demo" label="Passwort" :required="true" />
        <x-vdbs.forms.checkbox name="remember_demo" label="Angemeldet bleiben" />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" :full="true" size="l">Anmelden</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</div>

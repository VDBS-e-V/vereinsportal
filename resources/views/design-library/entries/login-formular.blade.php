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

<h2>Mit Bild neben dem Formular</h2>
<p>Die echte Anmeldeseite zeigt ein Bild, wenn in <code>config/portal.php</code> unter <code>login_image</code> eines eingetragen ist. Am Handy entfällt das Bild.</p>
<x-vdbs.layout.aside-layout variant="image" size="half" main-width="narrow" mobile="above">
    <x-vdbs.forms.form action="#" :summary="false">
        <x-vdbs.forms.input name="email_img" label="E-Mail-Adresse" type="email" autocomplete="username" :required="true" />
        <x-vdbs.forms.password name="password_img" label="Passwort" :required="true" />
        <x-vdbs.forms.checkbox name="remember_img" label="Angemeldet bleiben" />
        <x-slot:actions><x-vdbs.actions.button type="submit" :full="true" size="l">Anmelden</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
    <x-slot:aside><x-vdbs.media.image :src="placeholder('hero', 3)" alt="" credit-placement="edge" /></x-slot:aside>
</x-vdbs.layout.aside-layout>

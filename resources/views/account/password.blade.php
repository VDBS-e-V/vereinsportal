<x-account.shell title="Passwort" current="password" lead="Ändern Sie Ihr Passwort. Alle anderen Sitzungen werden dabei abgemeldet.">
    <div style="max-width:var(--container-narrow)">
        <x-vdbs.forms.form :action="route('account.password.update')" method="put" :summary="false">
            <x-vdbs.forms.password name="current_password" label="Aktuelles Passwort" :required="true" />
            <x-vdbs.forms.password name="password" label="Neues Passwort" :new="true" :required="true" :rules="['Mindestens 12 Zeichen', 'Groß- und Kleinbuchstaben', 'Mindestens eine Ziffer']" />
            <x-vdbs.forms.password name="password_confirmation" label="Neues Passwort wiederholen" :new="true" :required="true" />

            <x-slot:actions>
                <x-vdbs.actions.button type="submit">Passwort ändern</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    </div>
</x-account.shell>

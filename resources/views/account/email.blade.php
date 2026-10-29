<x-account.shell title="E-Mail-Adresse" current="email" lead="Mit dieser Adresse melden Sie sich an und erhalten Nachrichten vom Portal.">
    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Aktuelle Adresse">{{ $email }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    @if ($pending)
        <x-vdbs.feedback.alert variant="info" title="Änderung noch nicht bestätigt" style="margin-top:var(--space-5)">
            Wir haben einen Link an <strong>{{ $pending->new_email }}</strong> gesendet. Die Adresse ändert sich erst, wenn Sie ihn öffnen (gültig bis {{ $pending->expires_at->format('d.m.Y, H:i') }} Uhr). Ein neuer Antrag ersetzt diesen.
        </x-vdbs.feedback.alert>
    @endif

    <h2 style="margin-top:var(--space-8)">Adresse ändern</h2>

    <div style="max-width:var(--container-narrow)">
        <x-vdbs.forms.form :action="route('account.email.store')" :summary="false">
            <x-vdbs.forms.input name="new_email" label="Neue E-Mail-Adresse" type="email" :required="true" autocomplete="email" hint="An diese Adresse senden wir einen Bestätigungslink. Die alte Adresse erhält einen Hinweis." />
            <x-vdbs.forms.password name="password" label="Passwort zur Bestätigung" :required="true" />

            <x-slot:actions>
                <x-vdbs.actions.button type="submit" icon="mail">Bestätigungslink senden</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    </div>
</x-account.shell>

<x-account.shell title="Sicherheit" current="security" lead="Schützen Sie Ihr Konto mit einem zweiten Faktor, zusätzlich zum Passwort.">
    <x-slot:meta>
        @if ($enabled)
            <x-vdbs.data.badge tone="success">Zwei-Faktor aktiv</x-vdbs.data.badge>
        @else
            <x-vdbs.data.badge tone="warning">Nicht eingerichtet</x-vdbs.data.badge>
        @endif
    </x-slot:meta>

    @unless ($enabled)
        <x-vdbs.feedback.alert :variant="$required ? 'error' : 'warning'" :title="$required ? 'Zwei-Faktor ist für Ihre Rolle Pflicht' : 'Ihr Konto ist noch nicht durch einen zweiten Faktor geschützt'">
            @if ($required)
                Bis Sie eine Methode eingerichtet haben, sind nur diese Sicherheitsseiten erreichbar.
            @endif
            Richten Sie eine Authenticator-App oder den E-Mail-Code ein. Bei der Anmeldung geben Sie danach zusätzlich einen Sicherheitscode ein.
        </x-vdbs.feedback.alert>
    @else
        @if ($required)
            <x-vdbs.feedback.alert variant="info">Für Ihre Rolle ist die Zwei-Faktor-Anmeldung Pflicht. Die letzte Methode kann deshalb nicht entfernt werden.</x-vdbs.feedback.alert>
        @endif
    @endunless

    <div style="display:grid;gap:var(--space-5)">
        <x-vdbs.cards.card title="Authenticator-App">
            <p>Eine App wie Aegis, FreeOTP oder der Google Authenticator erzeugt alle 30 Sekunden einen neuen Code. Das ist die empfohlene Methode.</p>
            <p>@if ($hasTotp) <x-vdbs.data.badge tone="success">Eingerichtet</x-vdbs.data.badge> @else <x-vdbs.data.badge>Nicht eingerichtet</x-vdbs.data.badge> @endif</p>
            <x-slot:actions>
                @if ($hasTotp)
                    <x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2" data-vdbs-dialog-open="remove-totp">Entfernen</x-vdbs.actions.button>
                @else
                    <x-vdbs.actions.button :href="route('account.security.totp')" icon="shield">App einrichten</x-vdbs.actions.button>
                @endif
            </x-slot:actions>
        </x-vdbs.cards.card>

        <x-vdbs.cards.card title="E-Mail-Code">
            <p>Bei der Anmeldung senden wir einen Code an <strong>{{ $email }}</strong>. Praktisch als Ergänzung, aber weniger sicher als eine App.</p>
            <p>@if ($hasEmail) <x-vdbs.data.badge tone="success">Eingerichtet</x-vdbs.data.badge> @else <x-vdbs.data.badge>Nicht eingerichtet</x-vdbs.data.badge> @endif</p>
            <x-slot:actions>
                @if ($hasEmail)
                    <x-vdbs.actions.button variant="danger" shape="outline" icon="trash-2" data-vdbs-dialog-open="remove-email">Entfernen</x-vdbs.actions.button>
                @else
                    <x-vdbs.actions.button :href="route('account.security.email')" icon="mail">E-Mail-Code einrichten</x-vdbs.actions.button>
                @endif
            </x-slot:actions>
        </x-vdbs.cards.card>

        @if ($enabled)
            <x-vdbs.cards.card title="Wiederherstellungscodes">
                <p>Mit einem dieser Codes melden Sie sich an, wenn Ihre App oder Ihr Postfach nicht erreichbar ist. Jeder Code gilt einmal.</p>
                <p><strong>{{ $remaining }}</strong> von 10 Codes sind noch nicht verwendet.</p>
                @if ($remaining <= 2)
                    <x-vdbs.feedback.alert variant="warning">Es sind nur noch wenige Codes übrig. Erzeugen Sie neue Codes.</x-vdbs.feedback.alert>
                @endif
                <x-slot:actions>
                    <x-vdbs.actions.button variant="secondary" icon="refresh-cw" data-vdbs-dialog-open="new-codes">Neue Codes erzeugen</x-vdbs.actions.button>
                </x-slot:actions>
            </x-vdbs.cards.card>
        @endif
    </div>

    @if ($hasTotp)
        <x-vdbs.overlays.confirm-dialog id="remove-totp" title="Authenticator-App entfernen?" variant="danger" :action="route('account.security.destroy', 'totp')" method="delete" confirm-label="App entfernen">
            <p>Danach genügt bei der Anmeldung nicht mehr der Code aus der App. Bestätigen Sie die Änderung mit Ihrem Passwort.</p>
            <x-slot:fields><x-vdbs.forms.password id="remove-totp-password" name="password" label="Passwort" :required="true" /></x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif

    @if ($hasEmail)
        <x-vdbs.overlays.confirm-dialog id="remove-email" title="E-Mail-Code entfernen?" variant="danger" :action="route('account.security.destroy', 'email')" method="delete" confirm-label="E-Mail-Code entfernen">
            <p>Bei der Anmeldung wird dann kein Code mehr per E-Mail gesendet. Bestätigen Sie die Änderung mit Ihrem Passwort.</p>
            <x-slot:fields><x-vdbs.forms.password id="remove-email-password" name="password" label="Passwort" :required="true" /></x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif

    @if ($enabled)
        <x-vdbs.overlays.confirm-dialog id="new-codes" title="Neue Wiederherstellungscodes erzeugen?" :action="route('account.security.recovery.store')" confirm-label="Neue Codes erzeugen">
            <p>Die bisherigen Codes werden ungültig. Die neuen Codes sehen Sie danach einmalig. Bestätigen Sie mit Ihrem Passwort.</p>
            <x-slot:fields><x-vdbs.forms.password id="new-codes-password" name="password" label="Passwort" :required="true" /></x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-account.shell>

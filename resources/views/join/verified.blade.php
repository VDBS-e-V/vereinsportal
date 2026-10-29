<x-layouts.app title="E-Mail bestätigt" width="narrow">
    <x-vdbs.layout.page-header title="E-Mail-Adresse bestätigt" />

    <x-vdbs.feedback.alert variant="success" title="Ihr Konto ist aktiv">
        Sie können sich jetzt mit Ihrer E-Mail-Adresse und Ihrem Passwort anmelden. Sobald der Vorstand Ihren Antrag bestätigt hat, erhalten Sie Zugang zum Mitgliederbereich.
    </x-vdbs.feedback.alert>

    <p style="margin-top:var(--space-6)"><x-vdbs.actions.button :href="route('login')" icon="log-in">Zur Anmeldung</x-vdbs.actions.button></p>
</x-layouts.app>

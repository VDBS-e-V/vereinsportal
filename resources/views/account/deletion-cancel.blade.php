<x-layouts.app title="Löschung abbrechen" width="narrow">
    <x-vdbs.layout.page-header title="Kontolöschung abbrechen" />

    @if (($done ?? false) === true)
        <x-vdbs.feedback.alert variant="success" title="Die Löschung wurde abgebrochen">Ihr Konto ist wieder aktiv. Sie können sich jetzt anmelden.</x-vdbs.feedback.alert>
        <p style="margin-top:var(--space-6)"><x-vdbs.actions.button :href="route('login')" icon="log-in">Zur Anmeldung</x-vdbs.actions.button></p>
    @elseif ($pending)
        <p>Für dieses Konto ist die Löschung beantragt. Möchten Sie sie abbrechen und das Konto behalten?</p>
        <form method="post" action="{{ $action }}" style="margin-top:var(--space-5)">
            @csrf
            <x-vdbs.actions.button type="submit" icon="check">Löschung abbrechen</x-vdbs.actions.button>
        </form>
    @else
        <x-vdbs.feedback.alert variant="warning" title="Nichts abzubrechen">Für dieses Konto ist keine Löschung beantragt, oder es wurde schon gelöscht.</x-vdbs.feedback.alert>
        <p style="margin-top:var(--space-6)"><x-vdbs.actions.button variant="secondary" :href="route('login')">Zur Anmeldung</x-vdbs.actions.button></p>
    @endif
</x-layouts.app>

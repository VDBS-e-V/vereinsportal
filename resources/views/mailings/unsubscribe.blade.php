<x-layouts.app title="Mitteilungen abbestellen" width="narrow">
    <x-vdbs.layout.page-header title="Mitteilungen abbestellen" :lead="'Adresse: '.$masked" />

    <p>Es geht um: <strong>{{ $topicLabel }}</strong>.</p>

    @if ($unsubscribed)
        <x-vdbs.feedback.alert variant="success">Sie bekommen diese Mitteilungen nicht mehr. Andere E-Mails des Portals, etwa zur Anmeldung oder Sicherheit, sind davon nicht betroffen.</x-vdbs.feedback.alert>

        <form method="post" action="{{ route('mailings.unsubscribe.resubscribe', $token) }}" style="margin-top:var(--space-4)">
            @csrf
            <x-vdbs.actions.button type="submit" variant="secondary">Doch wieder erhalten</x-vdbs.actions.button>
        </form>
    @else
        <p>Möchten Sie diese Mitteilungen nicht mehr bekommen? Sie können das jederzeit wieder rückgängig machen. Wichtige Pflichtmitteilungen des Vereins an seine Mitglieder (zum Beispiel die Einladung zur Mitgliederversammlung) bleiben davon unberührt.</p>

        <form method="post" action="{{ route('mailings.unsubscribe.store', $token) }}" style="margin-top:var(--space-4)">
            @csrf
            <x-vdbs.actions.button type="submit" variant="primary">Abbestellen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>

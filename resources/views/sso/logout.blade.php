<x-sso.page title="Abmelden" heading="Abmelden">
    @if ($initiator)
        <p>Sie haben sich erfolgreich vom Dienst <strong>{{ $initiator }}</strong> abgemeldet.</p>
    @endif

    <p>Sie sind zusätzlich noch bei folgenden Diensten angemeldet:</p>
    <ol class="sso-services">
        <li>Vereinsportal</li>
        @foreach ($others as $service)
            <li>{{ $service['name'] }}</li>
        @endforeach
    </ol>

    <p>Wollen Sie versuchen, sich von diesen Diensten abzumelden?</p>

    <div class="sso-actions">
        <form method="post" action="{{ route('oidc.logout.perform') }}">
            @csrf
            <x-vdbs.actions.button type="submit" icon="log-out">Ja, abmelden</x-vdbs.actions.button>
        </form>
        <x-vdbs.actions.button variant="secondary" :href="$stay">Nein, angemeldet bleiben</x-vdbs.actions.button>
    </div>
</x-sso.page>

@php
    $labels = \App\Modules\Sso\Services\OidcClaims::SCOPES;
    $person = $user->person?->fullName();
    $wants = collect($scopes)->map(fn ($scope) => $labels[$scope->id] ?? $scope->description ?? $scope->id)->values();
@endphp
<x-layouts.app title="Anmeldung bei {{ $client->name }}" width="narrow">
    <x-vdbs.layout.page-header title="{{ $client->name }} möchte sich über das Portal anmelden" lead="Sie sind als {{ $person ?: $user->email }} angemeldet." />

    <p><strong>{{ $client->name }}</strong> möchte:</p>
    <ul>
        @foreach ($wants as $text)
            <li>{{ $text }}</li>
        @endforeach
    </ul>
    <p class="vdbs-hint">Ihr Passwort sieht {{ $client->name }} nie. Sie können die Freigabe jederzeit in Ihrem Konto unter „Verbundene Anwendungen“ widerrufen.</p>

    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-5)">
        <form method="post" action="{{ url('/oauth/authorize') }}">@csrf
            <input type="hidden" name="state" value="{{ $request->input('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-vdbs.actions.button type="submit" icon="check">Erlauben</x-vdbs.actions.button>
        </form>
        <form method="post" action="{{ url('/oauth/authorize') }}">@csrf @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->input('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-vdbs.actions.button type="submit" variant="secondary" icon="x">Ablehnen</x-vdbs.actions.button>
        </form>
    </div>
</x-layouts.app>

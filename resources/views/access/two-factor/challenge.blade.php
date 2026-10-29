@php
    $titles = ['totp' => 'Code aus der Authenticator-App', 'email' => 'Code aus der E-Mail', 'recovery' => 'Wiederherstellungscode'];
    $others = collect([
        'totp' => $hasTotp && $method !== 'totp' ? ['Authenticator-App verwenden', route('two-factor.challenge', ['method' => 'totp'])] : null,
        'email' => $hasEmail && $method !== 'email' ? ['Code per E-Mail erhalten', route('two-factor.challenge', ['method' => 'email'])] : null,
        'recovery' => $method !== 'recovery' ? ['Wiederherstellungscode verwenden', route('two-factor.challenge', ['method' => 'recovery'])] : null,
    ])->filter();
@endphp

<x-layouts.app title="Zwei-Faktor-Anmeldung" width="narrow">
    <h1>Sicherheitscode eingeben</h1>

    @if ($method === 'totp')
        <p>Öffnen Sie Ihre Authenticator-App und geben Sie den aktuellen sechsstelligen Code für das VDBS-Portal ein.</p>
    @elseif ($method === 'email')
        <p>Wir haben einen sechsstelligen Code an <strong>{{ $email }}</strong> gesendet. Er ist zehn Minuten gültig.</p>
    @else
        <p>Geben Sie einen Ihrer Wiederherstellungscodes ein. Jeder Code gilt nur einmal.</p>
    @endif

    <x-vdbs.forms.form :action="route('two-factor.verify')">
        <input type="hidden" name="method" value="{{ $method }}">

        @if ($method === 'recovery')
            <x-vdbs.forms.input name="code" :label="$titles[$method]" hint="Zum Beispiel ABCDE-FGHJK" :required="true" autocomplete="off" autofocus />
        @else
            <x-vdbs.forms.code name="code" label="Sicherheitscode" autofocus />
        @endif

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" :full="true" size="l">Bestätigen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($method === 'email')
        <form method="post" action="{{ route('two-factor.resend') }}" style="margin-top:var(--space-4)">
            @csrf
            <x-vdbs.actions.button type="submit" variant="tertiary" icon="send">Code erneut senden</x-vdbs.actions.button>
            @if ($resendIn > 0)
                <p style="margin:var(--space-1) 0 0;color:var(--text-muted);font-size:var(--font-size-small)">Ein neuer Code ist in {{ $resendIn }} Sekunden möglich.</p>
            @endif
        </form>
    @endif

    @if ($others->isNotEmpty())
        <h2 style="margin-top:var(--space-6);font-size:var(--font-size-h4)">Anderen Weg wählen</h2>
        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:var(--space-1)">
            @foreach ($others as [$label, $url])
                <li><x-vdbs.actions.button variant="tertiary" :href="$url">{{ $label }}</x-vdbs.actions.button></li>
            @endforeach
        </ul>
    @endif

    <p style="margin-top:var(--space-6)"><x-vdbs.actions.button variant="quiet" :href="route('login')" icon="arrow-left">Zurück zur Anmeldung</x-vdbs.actions.button></p>
</x-layouts.app>

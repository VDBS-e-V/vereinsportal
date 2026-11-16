@php
    $image = config('portal.login_image');
    $host = request()->getHost();
@endphp

@if ($image)
    <x-layouts.app title="Anmelden" width="wide">
        <x-vdbs.layout.aside-layout variant="image" size="half" main-width="narrow">
            @include('access._login-form')

            <x-slot:aside>
                <x-vdbs.media.image :src="$image['src']" :alt="$image['alt'] ?? ''" :credit="$image['credit'] ?? null" credit-placement="edge" loading="eager" />
            </x-slot:aside>
        </x-vdbs.layout.aside-layout>
    </x-layouts.app>
@else
    <x-sso.page title="Anmelden" :heading="$service['name'] ?? 'Anmeldung am Vereinsportal'" :lead="$service ? 'Sie melden sich über das Vereinsportal an.' : null">
        @include('access._login-form')

        <div class="sso-notes">
            <p class="sso-notes__title">Wichtige Hinweise:</p>
            <ul>
                <li>Bitte verwenden Sie die Zugangsdaten Ihres Vereinsportal-Kontos.</li>
                <li>Nach der Anmeldung können Sie weitere an das Vereinsportal angeschlossene Systeme nutzen, <strong>ohne</strong> erneut nach Passwort gefragt zu werden.</li>
                <li>Warnung vor Phishing: Der Verein bittet Sie niemals, Ihr Passwort auf Seiten außerhalb von {{ $host }} einzugeben.</li>
            </ul>
        </div>

        <x-slot:aside>
            <p class="sso-page__aside-title">{{ $service ? 'Beschreibung des Dienstes:' : 'Das Vereinsportal' }}</p>
            @if ($service && filled($service['description']))
                <p>{{ $service['description'] }}</p>
            @elseif ($service)
                <p>{{ $service['name'] }} ist an das Vereinsportal des VDBS e. V. angeschlossen. Ihre Anmeldung erfolgt zentral über das Portal.</p>
            @else
                <p>Das Vereinsportal ist die zentrale Anmeldung des Verbands für Demokratiebildung und Bibliotheken an Schulen e. V. Hier melden Sie sich einmal an und nutzen danach die angeschlossenen Systeme.</p>
            @endif
        </x-slot:aside>
    </x-sso.page>
@endif

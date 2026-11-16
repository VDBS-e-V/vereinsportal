<x-sso.page title="Abmelden" heading="Abmelden">
    <p>Sie haben sich vom Vereinsportal abgemeldet.@if ($services !== []) Es wird versucht, Sie von den folgenden Diensten abzumelden:@endif</p>

    <ol class="sso-services" data-sso-services>
        <li>Vereinsportal <span class="sso-services__state" data-state="done" aria-label="abgemeldet">✔</span></li>
        @foreach ($services as $service)
            <li data-sso-logout="{{ $service['url'] }}">{{ $service['name'] }} <span class="sso-services__state" data-state="pending" role="status">wird abgemeldet …</span></li>
        @endforeach
    </ol>

    @if ($services !== [])
        <noscript>
            @foreach ($services as $service)
                <iframe class="sso-frames" src="{{ $service['url'] }}" title="Abmeldung bei {{ $service['name'] }}" tabindex="-1"></iframe>
            @endforeach
        </noscript>
    @endif

    <div class="sso-actions">
        <x-vdbs.actions.button :href="$next" icon="arrow-right">Weiter</x-vdbs.actions.button>
    </div>
</x-sso.page>

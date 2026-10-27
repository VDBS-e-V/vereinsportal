@props(['title' => null])

@php
    $systems = collect(config('portal.systems'))->filter(fn (array $system): bool => filled($system['url']));
    $areas = [\App\Support\PortalAreas::Start, \App\Support\PortalAreas::Konto];
@endphp

<header class="vdbs-header">
    <div class="vdbs-header__top">
        <div class="vdbs-container vdbs-header__top-inner">
            <a class="vdbs-header__logo" href="{{ url('/') }}" aria-label="VDBS Vereinsportal – Startseite">
                <img src="{{ asset('images/brand/vdbs-logo.png') }}" alt="" width="32" height="32">
                <span class="vdbs-header__wordmark" aria-hidden="true">VDBS</span>
            </a>

            @if ($systems->isNotEmpty())
                <nav aria-label="Externe Systeme">
                    <ul class="vdbs-header__links">
                        @foreach ($systems as $system)
                            <li><a href="{{ $system['url'] }}">{{ $system['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </div>

    <div class="vdbs-container vdbs-header__main">
        <div>
            <p class="vdbs-header__eyebrow">VDBS Portal</p>
            <p class="vdbs-header__title">{{ $title ?? 'Start' }}</p>
        </div>

        <div class="vdbs-header__actions">
            @auth
                <nav aria-label="Hauptnavigation">
                    <ul class="vdbs-header__links vdbs-header__links--large">
                        @foreach ($areas as $area)
                            <li>
                                <a href="{{ $area === \App\Support\PortalAreas::Start ? route('home') : '#' }}" @if ($area === \App\Support\PortalAreas::Start && request()->routeIs('home')) aria-current="page" @endif>
                                    {{ $area->label() }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-vdbs.button type="submit" variant="secondary" icon="log-out">Abmelden</x-vdbs.button>
                </form>
            @else
                @unless (request()->routeIs('login'))
                    <x-vdbs.button :href="route('login')" icon="log-in">Login</x-vdbs.button>
                @endunless
            @endauth
        </div>
    </div>
</header>

@props(['title' => null, 'safe' => false])

@php
    $systems = collect(config('portal.systems'))->filter(fn (array $system): bool => filled($system['url']));
    $areas = [\App\Support\PortalAreas::Start, \App\Support\PortalAreas::Konto];
@endphp

{{--
    Kopfbereich (Paket "navigation"). Ab 1100 px zweizeilig mit allen Links; darunter (Handy und Tablet) klappen Bereiche, Systeme und
    Aktionen hinter dem Bars-Knopf "Menü" (aria-expanded, Esc und Klick daneben schließen). Ohne JavaScript bleibt
    alles sichtbar und untereinander gestapelt.
    safe: true auf Fehlerseiten; liest weder Sitzung noch Datenbank und zeigt die Ansicht für Gäste.
--}}
<header class="vdbs-header" data-vdbs-header>
    <div class="vdbs-header__top">
        <div class="vdbs-container vdbs-header__top-inner">
            <a class="vdbs-header__logo" href="{{ url('/') }}" aria-label="VDBS Vereinsportal – Startseite">
                <img src="{{ asset('images/brand/vdbs-bildmarke-breit.png') }}" alt="VDBS e. V." height="40">
            </a>

            <button type="button" class="vdbs-header__menu-toggle vdbs-js-only" data-vdbs-menu-toggle aria-expanded="false" aria-controls="portal-menu-areas portal-menu-actions" aria-label="Menü">
                <x-vdbs.icon class="vdbs-header__menu-icon-open" name="menu" :size="22" />
                <x-vdbs.icon class="vdbs-header__menu-icon-close" name="x" :size="22" />
            </button>

            <nav id="portal-menu-areas" class="vdbs-header__menu-panel" aria-label="Bereiche und externe Systeme">
                <ul class="vdbs-header__links">
                    {{-- Platzhalter: die Bereiche verlinken erst, wenn sie gebaut sind und per Rechte sichtbar werden. --}}
                    @foreach (\App\Support\PortalAreas::withGroupMark() as $topArea)
                        <li><a href="#">{{ $topArea->label() }}</a></li>
                    @endforeach
                    @foreach ($systems as $system)
                        <li><a href="{{ $system['url'] }}">{{ $system['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>

    <div class="vdbs-container vdbs-header__main">
        <div>
            <p class="vdbs-header__eyebrow">VDBS Portal</p>
            <p class="vdbs-header__title">{{ $title ?? 'Start' }}</p>
        </div>

        <div class="vdbs-header__actions vdbs-header__menu-panel" id="portal-menu-actions">
            @if (! $safe && auth()->check())
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

                @php $displayName = auth()->user()->person?->fullName() ?: auth()->user()->email; @endphp
                <x-vdbs.navigation.dropdown variant="plain" align="end" :label="$displayName">
                    <x-slot:trigger>
                        <x-vdbs.data.avatar :name="$displayName" size="s" aria-hidden="true" />
                        <span class="vdbs-header__user-name">{{ $displayName }}</span>
                    </x-slot:trigger>
                    {{-- Platzhalter, bis die Konto-Seiten gebaut sind --}}
                    <x-vdbs.navigation.dropdown-item href="#" icon="user">Mein Konto</x-vdbs.navigation.dropdown-item>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-vdbs.navigation.dropdown-item type="submit" icon="log-out">Abmelden</x-vdbs.navigation.dropdown-item>
                    </form>
                </x-vdbs.navigation.dropdown>
            @else
                <nav aria-label="Portal-Informationen">
                    <ul class="vdbs-header__links vdbs-header__links--large">
                        @foreach (config('portal.public_links') as $link)
                            <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                @unless (request()->routeIs('login'))
                    <x-vdbs.actions.button :href="route('login')" icon="log-in">Login</x-vdbs.actions.button>
                @endunless
            @endif
        </div>
    </div>
</header>

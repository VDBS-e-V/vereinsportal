@props(['title' => null, 'safe' => false])

@php
    $systems = collect(config('portal.systems'))->filter(fn (array $system): bool => filled($system['url']));
    $user = (! $safe && auth()->check()) ? auth()->user() : null;
    $routeName = $safe ? null : request()->route()?->getName();
    $visibleAreas = $user ? \App\Support\PortalNavigation::visibleAreas($user) : [];
    $currentArea = \App\Support\PortalNavigation::areaForRoute($routeName);
    // Ohne Anmeldung (und auf Fehlerseiten) stehen in Zeile 2 die öffentlichen Seiten des Bereichs Start.
    $navArea = $user === null ? \App\Support\PortalAreas::Start : $currentArea;
    $areaPages = $navArea ? \App\Support\PortalNavigation::pages($navArea, $user, $routeName) : [];
    $hasAreaMenu = count($visibleAreas) > 0 || $systems->isNotEmpty();
    $headerTitle = ($user !== null || $currentArea === \App\Support\PortalAreas::Start ? $currentArea?->label() : null) ?? ($title ?? 'Start');
@endphp

{{--
    Kopfbereich (Paket "navigation"). Zeile 1: die Bereiche des Nutzers (App\Support\PortalNavigation) und die Systeme; Zeile 2: Name
    des aktuellen Bereichs und dessen Seiten. Der Bereich wechselt nur durch Klick in Zeile 1. Ab 1100 px zweizeilig mit allen Links; darunter (Handy und Tablet) klappen Bereiche, Systeme und
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

            <button type="button" class="vdbs-header__menu-toggle vdbs-js-only" data-vdbs-menu-toggle aria-expanded="false" aria-controls="{{ $hasAreaMenu ? 'portal-menu-areas ' : '' }}portal-menu-actions" aria-label="Menü">
                <x-vdbs.icon class="vdbs-header__menu-icon-open" name="menu" :size="22" />
                <x-vdbs.icon class="vdbs-header__menu-icon-close" name="x" :size="22" />
            </button>

            @if ($hasAreaMenu)
                <nav id="portal-menu-areas" class="vdbs-header__menu-panel" aria-label="Bereiche und externe Systeme">
                    <ul class="vdbs-header__links">
                        @foreach ($visibleAreas as $topArea)
                            <li><a href="{{ \App\Support\PortalNavigation::home($topArea, $user) }}" @if ($topArea === $currentArea) aria-current="true" @endif>{{ $topArea->label() }}</a></li>
                        @endforeach
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
            <p class="vdbs-header__title">{{ $headerTitle }}</p>
        </div>

        <div class="vdbs-header__actions vdbs-header__menu-panel" id="portal-menu-actions">
            @if (count($areaPages) > 0)
                <nav class="vdbs-main-nav" aria-label="Seiten im Bereich {{ $navArea->label() }}">
                    <ul class="vdbs-header__links vdbs-header__links--large">
                        @foreach ($areaPages as $page)
                            <li @class(['vdbs-main-nav__item', 'vdbs-main-nav__item--has-submenu' => $page['children'] !== []])>
                                <a href="{{ $page['href'] }}" @if ($page['current']) aria-current="page" @endif>{{ $page['label'] }}</a>
                                @if ($page['children'] !== [])
                                    <ul class="vdbs-main-nav__submenu">
                                        @foreach ($page['children'] as $child)
                                            <li><a href="{{ $child['href'] }}" @if ($child['current']) aria-current="page" @endif>{{ $child['label'] }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($user)
                @php $displayName = $user->person?->fullName() ?: $user->email; @endphp
                <x-vdbs.navigation.dropdown variant="plain" align="end" :label="$displayName">
                    <x-slot:trigger>
                        <x-vdbs.data.avatar :name="$displayName" size="s" aria-hidden="true" />
                        <span class="vdbs-header__user-name">{{ $displayName }}</span>
                    </x-slot:trigger>
                    <x-vdbs.navigation.dropdown-item :href="route('account.profile')" icon="user" :current="request()->routeIs('account.profile')">Mein Konto</x-vdbs.navigation.dropdown-item>
                    <x-vdbs.navigation.dropdown-item :href="route('account.security.index')" icon="shield" :current="request()->routeIs('account.security.*')">Sicherheit</x-vdbs.navigation.dropdown-item>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-vdbs.navigation.dropdown-item type="submit" icon="log-out">Abmelden</x-vdbs.navigation.dropdown-item>
                    </form>
                </x-vdbs.navigation.dropdown>
            @elseif ($routeName !== 'login')
                <x-vdbs.actions.button :href="route('login')" icon="log-in">Login</x-vdbs.actions.button>
            @endif
        </div>
    </div>
</header>

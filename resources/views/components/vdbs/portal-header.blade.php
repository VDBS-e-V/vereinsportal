@props([
    'area' => 'VDBS Portal',
    'pageTitle' => 'Start',
    'homeUrl' => '#',
    'areaUrl' => null,
    'logoUrl' => null,
    'areas' => [],
    'navigation' => [],
    'breadcrumbs' => [],
    'loginUrl' => null,
    'account' => null,
    'preview' => false,
    'wide' => false,
])

@php
    $logoUrl ??= asset('images/brand/vdbs-bildmarke-breit.png');
    $areaUrl ??= $homeUrl;

    $linkClasses = static function (array $item, string $base): string {
        $classes = [$base];

        if (($item['active'] ?? false) === true) {
            $classes[] = $base . '--active';
        }

        if (($item['url'] ?? null) === null && ($item['children'] ?? []) === []) {
            $classes[] = $base . '--disabled';
        }

        return implode(' ', $classes);
    };
@endphp

<header
    class="site-header vdbs-portal-header{{ $preview ? ' site-header--preview vdbs-portal-header--preview' : '' }}{{ $wide ? ' site-header--wide vdbs-portal-header--wide' : '' }}"
    data-vdbs-portal-header width="full" gutter="both"
>
    <div class="header-top vdbs-portal-header__top">
        <div class="header-top__inner vdbs-portal-header__top-inner layout-frame layout-frame--full layout-frame--gutter">
            <a class="site-logo vdbs-portal-logo" href="{{ $homeUrl }}" aria-label="VDBS Startseite">
                <span class="site-logo__crop vdbs-portal-logo__crop" aria-hidden="true">
                    <img src="{{ $logoUrl }}" alt="">
                </span>
            </a>

            <nav class="header-areas vdbs-portal-areas" aria-label="Bereiche">
                @foreach ($areas as $item)
                    @php
    $children = $item['children'] ?? [];
    $url = $item['url'] ?? null;
                    @endphp

                    @if ($children !== [])
                        <div class="header-nav-group vdbs-portal-nav-group" data-vdbs-submenu>
                            <button
                                class="{{ $linkClasses($item, 'header-area-link') }}"
                                type="button"
                                data-vdbs-submenu-trigger
                                aria-expanded="false"
                            >
                                <span>{{ $item['label'] }}</span>
                                <x-vdbs.icon name="chevron-down" size="15" />
                            </button>

                            <div class="header-submenu vdbs-portal-submenu" data-vdbs-submenu-panel hidden>
                                @if ($url !== null)
                                    <a href="{{ $url }}">Übersicht</a>
                                @endif

                                @foreach ($children as $child)
                                    @if (($child['url'] ?? null) !== null)
                                        <a href="{{ $child['url'] }}">
                                            {{ $child['label'] }}
                                        </a>
                                    @else
                                        <span aria-disabled="true">
                                            {{ $child['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @elseif ($url !== null)
                        <a
                            class="{{ $linkClasses($item, 'header-area-link') }}"
                            href="{{ $url }}"
                            @if (($item['active'] ?? false) === true)
                                aria-current="page"
                            @endif
                        >
                            <span>{{ $item['label'] }}</span>

                            @if (($item['external'] ?? false) === true)
                                <x-vdbs.icon name="external-link" size="14" />
                            @endif
                        </a>
                    @else
                        <span class="{{ $linkClasses($item, 'header-area-link') }}" aria-disabled="true">
                            {{ $item['label'] }}
                        </span>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>

    <div class="header-bottom vdbs-portal-header__main">
        <div class="header-bottom__inner vdbs-portal-header__main-inner layout-frame layout-frame--full layout-frame--gutter">
            <a class="site-mobile-logo vdbs-portal-mobile-logo" href="{{ $homeUrl }}" aria-label="VDBS Startseite">
                <span class="site-mobile-logo__crop" aria-hidden="true">
                    <img src="{{ $logoUrl }}" alt="">
                </span>
            </a>

            <a class="header-page-context vdbs-portal-context" href="{{ $areaUrl }}">
                <span class="header-page-context__area vdbs-portal-context__area">
                    {{ $area }}
                </span>
                <strong class="header-page-context__page vdbs-portal-context__page">
                    {{ $pageTitle }}
                </strong>
            </a>

            <nav class="header-nav vdbs-portal-navigation" aria-label="Seitennavigation">
                @foreach ($navigation as $item)
                    @php
    $children = $item['children'] ?? [];
    $url = $item['url'] ?? null;
                    @endphp

                    @if ($children !== [])
                        <div class="header-nav-group vdbs-portal-nav-group" data-vdbs-submenu>
                            <button
                                class="{{ $linkClasses($item, 'header-nav-link') }}"
                                type="button"
                                data-vdbs-submenu-trigger
                                aria-expanded="false"
                            >
                                <span>{{ $item['label'] }}</span>
                                <x-vdbs.icon name="chevron-down" size="15" />
                            </button>

                            <div class="header-submenu vdbs-portal-submenu" data-vdbs-submenu-panel hidden>
                                @if ($url !== null)
                                    <a href="{{ $url }}">Übersicht</a>
                                @endif

                                @foreach ($children as $child)
                                    @if (($child['url'] ?? null) !== null)
                                        <a href="{{ $child['url'] }}">
                                            {{ $child['label'] }}
                                        </a>
                                    @else
                                        <span aria-disabled="true">
                                            {{ $child['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @elseif ($url !== null)
                        <a
                            class="{{ $linkClasses($item, 'header-nav-link') }}"
                            href="{{ $url }}"
                            @if (($item['active'] ?? false) === true)
                                aria-current="page"
                            @endif
                        >
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @else
                        <span class="{{ $linkClasses($item, 'header-nav-link') }}" aria-disabled="true">
                            {{ $item['label'] }}
                        </span>
                    @endif
                @endforeach
            </nav>

            <div class="header-account vdbs-portal-account">
                @if ($account !== null)
                    <div class="account-menu vdbs-account-menu" data-vdbs-account-menu>
                        <button
                            class="account-trigger vdbs-account-trigger"
                            type="button"
                            data-vdbs-account-trigger
                            aria-expanded="false"
                            aria-label="Benutzermenü öffnen"
                        >
                            @if (($account['avatar_url'] ?? null) !== null)
                                <img src="{{ $account['avatar_url'] }}" alt="">
                            @else
                                <span>{{ $account['initials'] ?? 'VB' }}</span>
                            @endif
                        </button>

                        <div class="account-dropdown vdbs-account-dropdown" data-vdbs-account-panel hidden>
                            <div class="account-summary vdbs-account-summary">
                                <div class="avatar avatar--large vdbs-avatar vdbs-avatar--large" aria-hidden="true">
                                    @if (($account['avatar_url'] ?? null) !== null)
                                        <img src="{{ $account['avatar_url'] }}" alt="">
                                    @else
                                        <span>{{ $account['initials'] ?? 'VB' }}</span>
                                    @endif
                                </div>

                                <div>
                                    <strong>{{ $account['name'] ?? 'Benutzerkonto' }}</strong>
                                    <span>{{ $account['handle'] ?? '' }}</span>
                                </div>
                            </div>

                            @foreach (($account['groups'] ?? []) as $group)
                                <div class="account-group vdbs-account-group">
                                    @foreach ($group as $item)
                                        @if (($item['url'] ?? null) !== null)
                                            <a class="account-link vdbs-account-link" href="{{ $item['url'] }}">
                                                <x-vdbs.icon :name="$item['icon'] ?? 'user'" size="19" />
                                                <span>{{ $item['label'] }}</span>
                                            </a>
                                        @else
                                            <span class="account-link account-link--disabled vdbs-account-link vdbs-account-link--disabled" aria-disabled="true">
                                                <x-vdbs.icon :name="$item['icon'] ?? 'user'" size="19" />
                                                <span>{{ $item['label'] }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach

                            @if (($account['logout'] ?? null) !== null)
                                <div class="account-group vdbs-account-group vdbs-account-group--logout">
                                    @if (($account['logout']['url'] ?? null) !== null)
                                        <a class="account-link vdbs-account-link" href="{{ $account['logout']['url'] }}">
                                            <x-vdbs.icon name="logout" size="19" />
                                            <span>{{ $account['logout']['label'] ?? 'Abmelden' }}</span>
                                        </a>
                                    @else
                                        <span class="account-link account-link--disabled vdbs-account-link vdbs-account-link--disabled" aria-disabled="true">
                                            <x-vdbs.icon name="logout" size="19" />
                                            <span>{{ $account['logout']['label'] ?? 'Abmelden' }}</span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif ($loginUrl !== null)
                    <a class="header-login vdbs-login-button" href="{{ $loginUrl }}">
                        <x-vdbs.icon name="login" size="19" />
                        <span>Login</span>
                    </a>
                @endif

                <button
                    class="mobile-menu-trigger vdbs-mobile-menu-trigger"
                    type="button"
                    data-vdbs-mobile-menu-trigger
                    aria-expanded="false"
                    aria-label="Menü öffnen"
                >
                    <x-vdbs.icon name="menu" size="27" />
                </button>
            </div>
        </div>
    </div>

    <div class="mobile-menu vdbs-mobile-menu" data-vdbs-mobile-menu hidden>
        <div class="mobile-menu__inner vdbs-mobile-menu__inner layout-frame layout-frame--full layout-frame--gutter">
            <div class="mobile-menu__group vdbs-mobile-menu__group">
                <strong class="mobile-menu__heading vdbs-mobile-menu__heading">Navigation</strong>

                @foreach ($navigation as $item)
                    @if (($item['url'] ?? null) !== null)
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @else
                        <span aria-disabled="true">{{ $item['label'] }}</span>
                    @endif

                    @foreach (($item['children'] ?? []) as $child)
                        @if (($child['url'] ?? null) !== null)
                            <a class="mobile-menu__child vdbs-mobile-menu__child" href="{{ $child['url'] }}">
                                {{ $child['label'] }}
                            </a>
                        @else
                            <span class="mobile-menu__child vdbs-mobile-menu__child" aria-disabled="true">
                                {{ $child['label'] }}
                            </span>
                        @endif
                    @endforeach
                @endforeach
            </div>

            <div class="mobile-menu__group vdbs-mobile-menu__group">
                <strong class="mobile-menu__heading vdbs-mobile-menu__heading">Bereiche</strong>

                @foreach ($areas as $item)
                    @if (($item['url'] ?? null) !== null)
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @else
                        <span aria-disabled="true">{{ $item['label'] }}</span>
                    @endif

                    @foreach (($item['children'] ?? []) as $child)
                        @if (($child['url'] ?? null) !== null)
                            <a class="mobile-menu__child vdbs-mobile-menu__child" href="{{ $child['url'] }}">
                                {{ $child['label'] }}
                            </a>
                        @else
                            <span class="mobile-menu__child vdbs-mobile-menu__child" aria-disabled="true">
                                {{ $child['label'] }}
                            </span>
                        @endif
                    @endforeach
                @endforeach
            </div>

            <div class="mobile-menu__group vdbs-mobile-menu__group">
                <strong class="mobile-menu__heading vdbs-mobile-menu__heading">Konto</strong>

                @if ($account !== null)
                    @foreach (($account['groups'] ?? []) as $group)
                        @foreach ($group as $item)
                            @if (($item['url'] ?? null) !== null)
                                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            @else
                                <span aria-disabled="true">{{ $item['label'] }}</span>
                            @endif
                        @endforeach
                    @endforeach

                    @if (($account['logout'] ?? null) !== null)
                        @if (($account['logout']['url'] ?? null) !== null)
                            <a href="{{ $account['logout']['url'] }}">
                                {{ $account['logout']['label'] ?? 'Abmelden' }}
                            </a>
                        @else
                            <span aria-disabled="true">
                                {{ $account['logout']['label'] ?? 'Abmelden' }}
                            </span>
                        @endif
                    @endif
                @elseif ($loginUrl !== null)
                    <a href="{{ $loginUrl }}">Login</a>
                @endif
            </div>
        </div>
    </div>
</header>

@if ($breadcrumbs !== [])
    <div class="header-breadcrumb vdbs-portal-breadcrumb-bar">
            <nav
                class="header-breadcrumb__inner vdbs-portal-breadcrumb-bar__inner layout-frame layout-frame--normal layout-frame--gutter"
                aria-label="Brotkrumen"
            >
                <ol class="breadcrumb vdbs-breadcrumb">
                    @foreach ($breadcrumbs as $crumb)
                        <li
                            @if ($loop->last)
                                aria-current="page"
                            @endif
                        >
                            @if (
            ($crumb['url'] ?? null) !== null
            && !$loop->last
        )
                                <a href="{{ $crumb['url'] }}">
                                    {{ $crumb['label'] }}
                                </a>
                            @else
                                {{ $crumb['label'] }}
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        </div>
    @endif

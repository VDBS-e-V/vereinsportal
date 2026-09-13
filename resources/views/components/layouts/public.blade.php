<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $routeName = request()->route()?->getName();

        $pageTitles = [
            'my.login' => 'Anmelden',
            'my.two-factor.challenge' => 'Zwei-Faktor-Anmeldung',
            'my.two-factor.method' => '2FA-Methode auswählen',
            'my.password.request' => 'Passwort vergessen',
            'my.password.reset' => 'Neues Passwort',
            'my.home' => 'Start',
            'portal.about' => 'Über das Portal',
            'portal.access' => 'Zugang zum Portal',
            'portal.faq' => 'FAQ',
            'portal.contact' => 'Kontakt',
            'portal.imprint' => 'Impressum',
            'portal.privacy' => 'Datenschutz',
            'portal.accessibility' => 'Barrierefreiheit',
            'my.account' => 'Konto',
            'my.account.profile' => 'Mein Profil',
            'my.account.settings' => 'Kontoeinstellungen',
            'my.membership' => 'Mitgliedschaft',
            'my.profile' => 'Kontodaten',
            'my.email-change' => 'E-Mail-Änderung',
            'my.password.change' => 'Passwort ändern',
            'my.security' => '2FA',
            'my.account-deletion' => 'Konto löschen',
            'my.registration.create' => 'Registrieren',
            'my.registration.status' => 'Registrierung',
            'identity.email-change.security' => 'Sicherheitshinweis',
        ];

        $pageTitle = $pageTitles[$routeName] ?? 'Vereinsportal';
        $documentTitle = $title ?? $pageTitle.' · VDBS Portal';
        $homeUrl = route('my.home');
        $user = auth()->user();
        $portalAreaCatalog = app(\App\Support\PortalAreaCatalog::class);
        $areas = $portalAreaCatalog->switcherAreas(
            $user instanceof \App\Modules\Identity\Models\User
                ? $user
                : null,
        );

        $settingsRouteNames = [
            'my.account.settings',
            'my.profile',
            'my.email-change',
            'my.password.change',
            'my.security',
            'my.account-deletion',
        ];

        $accountRouteNames = [
            'my.account',
            'my.account.profile',
            'my.membership',
            ...$settingsRouteNames,
        ];

        $personalAreaKey = null;

        if ($user instanceof \App\Modules\Identity\Models\User) {
            if (request()->routeIs('my.home')) {
                $personalAreaKey = \App\Support\PortalAreaCatalog::START;
            } elseif (request()->routeIs(...$accountRouteNames)) {
                $personalAreaKey = \App\Support\PortalAreaCatalog::PROFILE;
            }
        }

        $areaContext = $personalAreaKey !== null
            ? collect(
                $portalAreaCatalog->areas(
                    $user instanceof \App\Modules\Identity\Models\User
                        ? $user
                        : null,
                    $personalAreaKey,
                )
            )->firstWhere('key', $personalAreaKey)
            : null;
        $areaLabel = $areaContext['label'] ?? 'VDBS Portal';
        $areaUrl = $areaContext['url'] ?? $homeUrl;

        $settingsNavigation = [
            [
                'label' => 'Kontodaten',
                'url' => route('my.profile'),
                'active' => request()->routeIs('my.profile'),
            ],
            [
                'label' => '2FA',
                'url' => route('my.security'),
                'active' => request()->routeIs('my.security'),
            ],
            [
                'label' => 'E-Mail-Änderung',
                'url' => route('my.email-change'),
                'active' => request()->routeIs('my.email-change'),
            ],
            [
                'label' => 'Passwort ändern',
                'url' => route('my.password.change'),
                'active' => request()->routeIs('my.password.change'),
            ],
            [
                'label' => 'Konto löschen',
                'url' => route('my.account-deletion'),
                'active' => request()->routeIs('my.account-deletion'),
            ],
        ];

        $accountAccess = app(\App\Modules\Identity\Support\AccountAccess::class);
        $hasMembershipArea = $user instanceof \App\Modules\Identity\Models\User
            && $accountAccess->hasActiveRole(
                $user,
                \App\Modules\Identity\Enums\RoleKey::Member,
            );
        $hasTeamArea = $user instanceof \App\Modules\Identity\Models\User
            && $accountAccess->hasActiveRole(
                $user,
                \App\Modules\Identity\Enums\RoleKey::Team,
            );

        $accountAreaNavigation = [
            [
                'label' => 'Mein Profil',
                'url' => route('my.account.profile'),
                'active' => request()->routeIs('my.account.profile'),
            ],
            [
                'label' => 'Kontoeinstellungen',
                'url' => route('my.account.settings'),
                'active' => request()->routeIs(...$settingsRouteNames),
            ],
        ];

        if ($hasMembershipArea) {
            $accountAreaNavigation[] = [
                'label' => 'Mitgliedschaft',
                'url' => route('my.membership'),
                'active' => request()->routeIs('my.membership'),
            ];
        }

        if ($hasTeamArea) {
            $accountAreaNavigation[] = [
                'label' => 'Teamendeneinstellungen',
                'url' => null,
                'active' => false,
            ];
        }

        $accountAreaNavigation[] = [
            'label' => 'Meine Tickets',
            'url' => null,
            'active' => false,
        ];

        $portalNavigation = [
            [
                'label' => 'Über das Portal',
                'url' => route('portal.about'),
                'active' => request()->routeIs('portal.about'),
            ],
            [
                'label' => 'Zugang zum Portal',
                'url' => route('portal.access'),
                'active' => request()->routeIs('portal.access'),
            ],
            [
                'label' => 'FAQ',
                'url' => route('portal.faq'),
                'active' => request()->routeIs('portal.faq'),
            ],
            [
                'label' => 'Kontakt',
                'url' => route('portal.contact'),
                'active' => request()->routeIs('portal.contact'),
            ],
        ];

        $showSettingsNavigation = auth()->check()
            && request()->routeIs(...$settingsRouteNames);

        if (auth()->check()) {
            $navigation = [
                [
                    'label' => 'Start',
                    'url' => route('my.home'),
                    'active' => request()->routeIs('my.home'),
                ],
                [
                    'label' => 'Konto',
                    'url' => route('my.account'),
                    'active' => request()->routeIs(...$accountRouteNames),
                    'children' => $accountAreaNavigation,
                ],
                ...$portalNavigation,
            ];
        } else {
            $navigation = $portalNavigation;
        }

        $breadcrumbs = [];

        if (! request()->routeIs('my.home')) {
            $breadcrumbs[] = [
                'label' => 'VDBS Portal',
                'url' => $homeUrl,
            ];

            if (
                auth()->check()
                && request()->routeIs(...$accountRouteNames)
            ) {
                if (! request()->routeIs('my.account')) {
                    $breadcrumbs[] = [
                        'label' => 'Konto',
                        'url' => route('my.account'),
                    ];
                }

                if (
                    request()->routeIs(...$settingsRouteNames)
                    && ! request()->routeIs('my.account.settings')
                ) {
                    $breadcrumbs[] = [
                        'label' => 'Kontoeinstellungen',
                        'url' => route('my.account.settings'),
                    ];
                }
            }

            $breadcrumbs[] = [
                'label' => $pageTitle,
                'url' => null,
            ];
        }

        $account = null;

        if ($user !== null) {
            $person = $user->person;
            $displayName = trim(
                ($person?->first_name ?? '').' '.
                ($person?->last_name ?? '')
            );

            if ($displayName === '') {
                $displayName = $user->email;
            }

            $initialSource = $person !== null
                ? trim($person->first_name.' '.$person->last_name)
                : \Illuminate\Support\Str::before($user->email, '@');

            $initials = collect(
                preg_split('/[\s._-]+/u', $initialSource) ?: []
            )
                ->filter()
                ->take(2)
                ->map(
                    fn (string $part): string =>
                        \Illuminate\Support\Str::upper(
                            \Illuminate\Support\Str::substr($part, 0, 1)
                        )
                )
                ->implode('');

            $accountMenuItems = [
                [
                    'label' => 'Mein Profil',
                    'icon' => 'user',
                    'url' => route('my.account.profile'),
                ],
                [
                    'label' => 'Kontoeinstellungen',
                    'icon' => 'settings',
                    'url' => route('my.account.settings'),
                ],
            ];

            if ($hasMembershipArea) {
                $accountMenuItems[] = [
                    'label' => 'Mitgliedschaft',
                    'icon' => 'records',
                    'url' => route('my.membership'),
                ];
            }

            if ($hasTeamArea) {
                $accountMenuItems[] = [
                    'label' => 'Teamendeneinstellungen',
                    'icon' => 'users',
                    'url' => null,
                ];
            }

            $accountMenuItems[] = [
                'label' => 'Meine Tickets',
                'icon' => 'ticket',
                'url' => null,
            ];

            $account = [
                'name' => $displayName,
                'handle' => $user->email,
                'initials' => $initials !== '' ? $initials : 'VB',
                'groups' => [
                    $accountMenuItems,
                    [
                        [
                            'label' => 'Kontakt',
                            'icon' => 'mail',
                            'url' => route('portal.contact'),
                        ],
                        [
                            'label' => 'FAQ',
                            'icon' => 'help',
                            'url' => route('portal.faq'),
                        ],
                        [
                            'label' => 'Hilfe',
                            'icon' => 'help',
                            'url' => route('portal.faq'),
                        ],
                    ],
                ],
                'logout' => [
                    'label' => 'Abmelden',
                    'url' => route('my.logout'),
                    'method' => 'post',
                ],
            ];
        }

        $footerLinks = [
            [
                'label' => 'Start',
                'url' => $homeUrl,
            ],
        ];

        if (auth()->check()) {
            $footerLinks[] = [
                'label' => 'Konto',
                'url' => route('my.account'),
            ];
        } else {
            $footerLinks[] = [
                'label' => 'Anmelden',
                'url' => route('my.login'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('design.index')) {
            $footerLinks[] = [
                'label' => 'Designsystem',
                'url' => route('design.index'),
            ];
        }
    @endphp

    <title>{{ $documentTitle }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @livewireStyles
</head>

<body class="vdbs-app-shell">
    <a class="vdbs-skip-link" href="#main-content">
        Zum Inhalt
    </a>

    <x-vdbs.portal-header
        :area="$areaLabel"
        :page-title="$pageTitle"
        :home-url="$homeUrl"
        :area-url="$areaUrl"
        :areas="$areas"
        :navigation="$navigation"
        :breadcrumbs="$breadcrumbs"
        :login-url="auth()->check() ? null : route('my.login')"
        :account="$account"
    />

    @if ($showSettingsNavigation)
        <div class="account-local-navigation">
            <x-vdbs.frame width="normal" gutter="both">
                <nav class="local-nav" aria-label="Kontoeinstellungen">
                    <ul class="local-nav__list">
                        @foreach ($settingsNavigation as $item)
                            <li>
                                <a
                                    class="local-nav__link"
                                    href="{{ $item['url'] }}"
                                    @if ($item['active'])
                                        aria-current="page"
                                    @endif
                                >
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </x-vdbs.frame>
        </div>
    @endif

    <main id="main-content" class="site-main vdbs-public-main">
        <x-vdbs.frame width="normal" gutter="both">
            {{ $slot }}
        </x-vdbs.frame>
    </main>

    <x-vdbs.portal-footer
        :home-url="$homeUrl"
        :links="$footerLinks"
    />

    @livewireScripts
</body>

</html>

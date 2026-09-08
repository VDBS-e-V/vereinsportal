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
            'my.password.request' => 'Passwort vergessen',
            'my.password.reset' => 'Neues Passwort',
            'my.home' => 'Start',
            'my.profile' => 'Profil',
            'my.email-change' => 'E-Mail-Adresse',
            'my.password.change' => 'Passwort',
            'my.security' => 'Sicherheit',
            'my.account-deletion' => 'Konto löschen',
            'my.registration.create' => 'Registrieren',
            'my.registration.status' => 'Registrierung',
            'identity.email-change.security' => 'Sicherheitshinweis',
        ];

        $pageTitle = $pageTitles[$routeName] ?? 'Vereinsportal';
        $documentTitle = $title ?? $pageTitle.' · VDBS Portal';
        $homeUrl = route('my.home');

        $areas = [
            [
                'label' => 'Verwaltung',
                'url' => null,
            ],
        ];

        if (\Illuminate\Support\Facades\Route::has('design.index')) {
            $areas[] = [
                'label' => 'Design',
                'url' => route('design.index'),
            ];
        }

        $navigation = [];

        if (auth()->check()) {
            $navigation = [
                [
                    'label' => 'Start',
                    'url' => route('my.home'),
                    'active' => request()->routeIs('my.home'),
                ],
                [
                    'label' => 'Profil',
                    'url' => route('my.profile'),
                    'active' => request()->routeIs('my.profile'),
                ],
                [
                    'label' => 'E-Mail',
                    'url' => route('my.email-change'),
                    'active' => request()->routeIs('my.email-change'),
                ],
                [
                    'label' => 'Passwort',
                    'url' => route('my.password.change'),
                    'active' => request()->routeIs('my.password.change'),
                ],
                [
                    'label' => 'Sicherheit',
                    'url' => route('my.security'),
                    'active' => request()->routeIs('my.security'),
                ],
            ];

            if (\Illuminate\Support\Facades\Route::has('my.account-deletion')) {
                $navigation[] = [
                    'label' => 'Konto löschen',
                    'url' => route('my.account-deletion'),
                    'active' => request()->routeIs('my.account-deletion'),
                ];
            }
        } else {
            $navigation = [
                [
                    'label' => 'Anmelden',
                    'url' => route('my.login'),
                    'active' => request()->routeIs('my.login'),
                ],
                [
                    'label' => 'Registrieren',
                    'url' => route('my.registration.create'),
                    'active' => request()->routeIs('my.registration.create'),
                ],
                [
                    'label' => 'Passwort vergessen',
                    'url' => route('my.password.request'),
                    'active' => request()->routeIs('my.password.request'),
                ],
            ];
        }

        $breadcrumbs = [];

        if (! request()->routeIs('my.home')) {
            $breadcrumbs = [
                [
                    'label' => 'VDBS Portal',
                    'url' => $homeUrl,
                ],
                [
                    'label' => $pageTitle,
                    'url' => null,
                ],
            ];
        }

        $account = null;
        $user = auth()->user();

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

            $account = [
                'name' => $displayName,
                'handle' => $user->email,
                'initials' => $initials !== '' ? $initials : 'VB',
                'groups' => [
                    [
                        [
                            'label' => 'Mein Profil',
                            'icon' => 'user',
                            'url' => route('my.profile'),
                        ],
                        [
                            'label' => 'Kontoeinstellungen',
                            'icon' => 'settings',
                            'url' => route('my.security'),
                        ],
                        [
                            'label' => 'Meine Tickets',
                            'icon' => 'ticket',
                            'url' => null,
                        ],
                    ],
                    [
                        [
                            'label' => 'Kontakt',
                            'icon' => 'mail',
                            'url' => null,
                        ],
                        [
                            'label' => 'FAQ',
                            'icon' => 'help',
                            'url' => null,
                        ],
                        [
                            'label' => 'Hilfe',
                            'icon' => 'help',
                            'url' => null,
                        ],
                    ],
                ],
                'logout' => [
                    'label' => 'Abmelden',
                    'url' => null,
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
                'label' => 'Profil',
                'url' => route('my.profile'),
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
        area="VDBS Portal"
        :page-title="$pageTitle"
        :home-url="$homeUrl"
        :area-url="$homeUrl"
        :areas="$areas"
        :navigation="$navigation"
        :breadcrumbs="$breadcrumbs"
        :login-url="auth()->check() ? null : route('my.login')"
        :account="$account"
    />

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
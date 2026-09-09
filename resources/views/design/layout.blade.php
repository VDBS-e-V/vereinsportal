<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @yield('title') · VDBS Designsystem
    </title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="design-workbench">
    @php
        $designRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(
                fn ($route): bool => str_starts_with(
                    (string) $route->getName(),
                    'design.',
                )
            )
            ->filter(
                fn ($route): bool => $route->parameterNames() === []
            )
            ->sortBy(function ($route): string {
                $name = (string) $route->getName();
                $relativeName = \Illuminate\Support\Str::after(
                    $name,
                    'design.',
                );
                $rootSegment = \Illuminate\Support\Str::before(
                    $relativeName,
                    '.',
                );
                $rootOrder = [
                    'index' => 0,
                    'elemente' => 10,
                    'muster' => 20,
                    'grundlagen' => 30,
                    'layout' => 40,
                    'header' => 50,
                    'vorlagen' => 60,
                    'print' => 70,
                ];
                $rank = $rootOrder[$rootSegment] ?? 90;

                return str_pad(
                    (string) $rank,
                    3,
                    '0',
                    STR_PAD_LEFT,
                ).':'.$name;
            })
            ->values();

        $designRouteLabel = static function ($route): string {
            $routeName = (string) $route->getName();
            $fallbackLabel = collect(
                explode(
                    '.',
                    \Illuminate\Support\Str::after(
                        $routeName,
                        'design.',
                    ),
                )
            )
                ->map(
                    fn (string $segment): string =>
                        \Illuminate\Support\Str::headline($segment)
                )
                ->implode(' / ');

            return $route->defaults['design_title']
                ?? $fallbackLabel;
        };

        $currentDesignTitle = request()->route()?->defaults['design_title']
            ?? 'Übersicht';

        $designNavigationItems = $designRoutes
            ->map(function ($route) use ($designRouteLabel): array {
                $routeName = (string) $route->getName();
                $relativeName = \Illuminate\Support\Str::after(
                    $routeName,
                    'design.',
                );

                $segments = explode('.', $relativeName);

                return [
                    'label' => $designRouteLabel($route),
                    'url' => route($routeName),
                    'active' => request()->routeIs($routeName),
                    'route_name' => $routeName,
                    'segments' => $segments,
                    'navigation_group' =>
                        $route->defaults['design_navigation_group']
                        ?? $segments[0],
                ];
            });

        $rootDesignNavigation = $designNavigationItems
            ->filter(fn (array $item): bool => count($item['segments']) === 1)
            ->keyBy(fn (array $item): string => $item['segments'][0]);

        $nestedDesignNavigation = $designNavigationItems
            ->filter(fn (array $item): bool => count($item['segments']) > 1)
            ->groupBy(
                fn (array $item): string =>
                    $item['navigation_group']
            );

        $designNavigation = $rootDesignNavigation
            ->map(function (array $item, string $key) use ($nestedDesignNavigation): array {
                $children = $nestedDesignNavigation
                    ->get($key, collect())
                    ->map(fn (array $child): array => [
                        'label' => $child['label'],
                        'url' => $child['url'],
                        'active' => $child['active'],
                    ])
                    ->values()
                    ->all();

                return [
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'active' => $item['active'] || collect($children)->contains('active', true),
                    'children' => $children,
                ];
            })
            ->values();

        $nestedWithoutRoot = $nestedDesignNavigation
            ->reject(fn ($items, string $key): bool => $rootDesignNavigation->has($key))
            ->map(function ($items, string $key): array {
                $children = collect($items)
                    ->map(fn (array $child): array => [
                        'label' => $child['label'],
                        'url' => $child['url'],
                        'active' => $child['active'],
                    ])
                    ->values()
                    ->all();

                return [
                    'label' => \Illuminate\Support\Str::headline($key),
                    'url' => null,
                    'active' => collect($children)->contains('active', true),
                    'children' => $children,
                ];
            })
            ->values();

        $designNavigation = $designNavigation
            ->concat($nestedWithoutRoot)
            ->all();

        $designAdministrationUser = auth()->user();
        $hasAdministrationAccess =
            $designAdministrationUser instanceof \App\Modules\Identity\Models\User
            && app(\App\Modules\Administration\Support\AdministrationAccess::class)
                ->allows($designAdministrationUser);

        $designAreas = [
            [
                'label' => 'Verwaltung',
                'url' => $hasAdministrationAccess
                    && \Illuminate\Support\Facades\Route::has('administration.home')
                        ? route('administration.home')
                        : null,
            ],
            [
                'label' => 'Design',
                'url' => route('design.index'),
                'active' => true,
            ],
        ];

        $designAccount = null;
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

            $designAccount = [
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
                    'url' => route('my.logout'),
                    'method' => 'post',
                ],
            ];
        }

        $designBreadcrumbs = [
            [
                'label' => 'Design',
                'url' => route('design.index'),
            ],
        ];

        $currentDesignRoute = request()->route();
        $currentDesignRouteName = (string) $currentDesignRoute?->getName();

        if (
            $currentDesignRouteName !== ''
            && $currentDesignRouteName !== 'design.index'
        ) {
            $relativeRouteName = \Illuminate\Support\Str::after(
                $currentDesignRouteName,
                'design.',
            );
            $breadcrumbSegments = explode('.', $relativeRouteName);
            $sourceRootSegment = $breadcrumbSegments[0];
            $navigationGroup =
                $currentDesignRoute?->defaults['design_navigation_group']
                ?? $sourceRootSegment;
            $groupRouteName = 'design.'.$navigationGroup;
            $groupItem = $designNavigationItems
                ->firstWhere('route_name', $groupRouteName);
            $isGroupCurrent =
                $currentDesignRouteName === $groupRouteName;

            $designBreadcrumbs[] = [
                'label' => $groupItem['label']
                    ?? \Illuminate\Support\Str::headline($navigationGroup),
                'url' => $isGroupCurrent
                    ? null
                    : ($groupItem['url'] ?? null),
            ];

            if (! $isGroupCurrent) {
                $remainingBreadcrumbSegments = array_slice(
                    $breadcrumbSegments,
                    1,
                );
                $breadcrumbRouteSegments = [$sourceRootSegment];

                foreach (
                    $remainingBreadcrumbSegments
                    as $index => $segment
                ) {
                    $breadcrumbRouteSegments[] = $segment;

                    $breadcrumbRouteName = 'design.'.implode(
                        '.',
                        $breadcrumbRouteSegments,
                    );
                    $breadcrumbItem = $designNavigationItems
                        ->firstWhere('route_name', $breadcrumbRouteName);
                    $isLastBreadcrumb = $index === array_key_last(
                        $remainingBreadcrumbSegments
                    );

                    $designBreadcrumbs[] = [
                        'label' => $breadcrumbItem['label']
                            ?? \Illuminate\Support\Str::headline($segment),
                        'url' => $isLastBreadcrumb
                            ? null
                            : ($breadcrumbItem['url'] ?? null),
                    ];
                }
            }
        }
    @endphp

    <a class="vdbs-skip-link" href="#design-content">
        Zum Inhalt
    </a>

    <x-vdbs.portal-header
        area="Designsystem"
        :page-title="$currentDesignTitle"
        :home-url="route('my.home')"
        :area-url="route('design.index')"
        :areas="$designAreas"
        :navigation="$designNavigation"
        :account="$designAccount"
        :breadcrumbs="$designBreadcrumbs"
    />

    <main id="design-content" class="site-main design-main">
        <div class="container design-main-inner">
            @yield('content')
        </div>
    </main>

    <x-vdbs.portal-footer
        :home-url="route('my.home')"
        :links="[
            [
                'label' => 'Vereinsportal',
                'url' => route('my.home'),
            ],
            [
                'label' => 'Designsystem',
                'url' => route('design.index'),
            ],
        ]"
    />
</body>

</html>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Interner Bereich') · VDBS Portal</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="vdbs-app-shell">
    @php
        $pageTitle = trim($__env->yieldContent('title')) ?: 'Übersicht';
        $portalHomeUrl = route('my.home');
        $user = auth()->user();

        $administrationAccess = app(\App\Modules\Administration\Support\AdministrationAccess::class);
        $administrationCapabilities = $user instanceof \App\Modules\Identity\Models\User
            ? array_map(
                fn (\App\Modules\Administration\Enums\AdministrationCapability $capability): string => $capability->value,
                $administrationAccess->capabilities($user),
            )
            : [];
        $can = static fn (\App\Modules\Administration\Enums\AdministrationCapability $capability): bool => in_array(
            $capability->value,
            $administrationCapabilities,
            true,
        );

        $isBoardArea = request()->is('vorstand', 'vorstand/*');
        $isCoordinationArea = request()->is('koordination', 'koordination/*');
        $isAdministrationArea = ! $isBoardArea && ! $isCoordinationArea;

        $areaLabel = $isBoardArea
            ? 'Vorstand'
            : ($isCoordinationArea ? 'Koordination' : 'Verwaltung');
        $areaHomeUrl = $isBoardArea
            ? route('board.home')
            : ($isCoordinationArea ? route('coordination.home') : route('administration.home'));

        $areas = [];
        if ($can(\App\Modules\Administration\Enums\AdministrationCapability::AdministrationAreaAccess)) {
            $areas[] = [
                'label' => 'Verwaltung',
                'url' => route('administration.home'),
                'active' => $isAdministrationArea,
            ];
        }
        if ($can(\App\Modules\Administration\Enums\AdministrationCapability::BoardAreaAccess)) {
            $areas[] = [
                'label' => 'Vorstand',
                'url' => route('board.home'),
                'active' => $isBoardArea,
            ];
        }
        if ($can(\App\Modules\Administration\Enums\AdministrationCapability::CoordinationAreaAccess)) {
            $areas[] = [
                'label' => 'Koordination',
                'url' => route('coordination.home'),
                'active' => $isCoordinationArea,
            ];
        }
        if (\Illuminate\Support\Facades\Route::has('design.index')) {
            $areas[] = ['label' => 'Design', 'url' => route('design.index')];
        }

        $navigation = [
            ['label' => 'Übersicht', 'url' => $areaHomeUrl, 'active' => request()->url() === $areaHomeUrl],
        ];

        if ($isAdministrationArea) {
            if ($can(\App\Modules\Administration\Enums\AdministrationCapability::PersonsRead)) {
                $navigation[] = ['label' => 'Personen', 'url' => route('administration.persons.index'), 'active' => request()->routeIs('administration.persons.*')];
            }
            if ($can(\App\Modules\Administration\Enums\AdministrationCapability::UsersRead)) {
                $navigation[] = ['label' => 'Benutzer', 'url' => route('administration.users.index'), 'active' => request()->routeIs('administration.users.*')];
            }
            if ($can(\App\Modules\Administration\Enums\AdministrationCapability::CommunicationRead)) {
                $navigation[] = ['label' => 'Kommunikation', 'url' => route('administration.communication.templates.index'), 'active' => request()->routeIs('administration.communication.*')];
            }
            if ($can(\App\Modules\Administration\Enums\AdministrationCapability::AuditRead)) {
                $navigation[] = [
                    'label' => 'Audit',
                    'url' => route('administration.audit.index'),
                    'active' => request()->routeIs('administration.audit.*'),
                ];
            }
        }

        if (
            $isBoardArea
            && $can(\App\Modules\Administration\Enums\AdministrationCapability::MembershipsRead)
        ) {
            $navigation[] = [
                'label' => 'Mitgliedschaften',
                'url' => route('administration.memberships.index'),
                'active' => request()->routeIs(
                    'administration.memberships.*',
                    'administration.persons.memberships.*',
                ),
            ];
        }

        $account = null;
        if ($user instanceof \App\Modules\Identity\Models\User) {
            $person = $user->person;
            $displayName = trim(($person?->first_name ?? '').' '.($person?->last_name ?? ''));
            if ($displayName === '') {
                $displayName = $user->email;
            }
            $initialSource = $person !== null
                ? trim($person->first_name.' '.$person->last_name)
                : \Illuminate\Support\Str::before($user->email, '@');
            $initials = collect(preg_split('/[\s._-]+/u', $initialSource) ?: [])
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                ->implode('');
            $account = [
                'name' => $displayName,
                'handle' => $user->email,
                'initials' => $initials !== '' ? $initials : 'VB',
                'groups' => [[
                    ['label' => 'Konto', 'icon' => 'user', 'url' => route('my.account')],
                    ['label' => 'Kontoeinstellungen', 'icon' => 'settings', 'url' => route('my.account.settings')],
                ]],
                'logout' => ['label' => 'Abmelden', 'url' => route('my.logout'), 'method' => 'post'],
            ];
        }
    @endphp
    <a class="vdbs-skip-link" href="#administration-content">Zum Inhalt</a>
    <x-vdbs.portal-header :area="$areaLabel" :page-title="$pageTitle" :home-url="$portalHomeUrl" :area-url="$areaHomeUrl" :areas="$areas" :navigation="$navigation" :account="$account" :breadcrumbs="$breadcrumbs ?? []" />
    <main id="administration-content" class="site-main vdbs-public-main">
        <x-vdbs.frame width="normal" gutter="both">
            @if (session('status'))
                @php
                    $administrationStatusType = session('status_type', 'info');
                    $administrationStatusRole = in_array($administrationStatusType, ['danger', 'warning'], true) ? 'alert' : 'status';
                @endphp
                <x-vdbs.notice :type="$administrationStatusType" :role="$administrationStatusRole">{{ session('status') }}</x-vdbs.notice>
            @endif
            @yield('content')
        </x-vdbs.frame>
    </main>
    <x-vdbs.portal-footer :home-url="$portalHomeUrl" :links="[['label' => 'Vereinsportal', 'url' => $portalHomeUrl], ['label' => $areaLabel, 'url' => $areaHomeUrl]]" />
</body>
</html>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Verwaltung') · VDBS Portal</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="vdbs-app-shell">
    @php
        $pageTitle = trim($__env->yieldContent('title')) ?: 'Verwaltung';
        $homeUrl = route('administration.home');
        $user = auth()->user();
        $areas = [
            ['label' => 'Portal', 'url' => route('my.home')],
            ['label' => 'Verwaltung', 'url' => $homeUrl, 'active' => true],
        ];
        if (\Illuminate\Support\Facades\Route::has('design.index')) {
            $areas[] = ['label' => 'Design', 'url' => route('design.index')];
        }
        $navigation = [
            ['label' => 'Übersicht', 'url' => $homeUrl, 'active' => request()->routeIs('administration.home')],
            ['label' => 'Personen', 'url' => route('administration.persons.index'), 'active' => request()->routeIs('administration.persons.*')],
            ['label' => 'Mitgliedschaften', 'url' => route('administration.memberships.index'), 'active' => request()->routeIs('administration.memberships.*')],
            ['label' => 'Benutzer', 'url' => route('administration.users.index'), 'active' => request()->routeIs('administration.users.*')],
        ];
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
                    ['label' => 'Mein Profil', 'icon' => 'user', 'url' => route('my.profile')],
                    ['label' => 'Sicherheit', 'icon' => 'lock', 'url' => route('my.security')],
                ]],
                'logout' => ['label' => 'Abmelden', 'url' => route('my.logout'), 'method' => 'post'],
            ];
        }
    @endphp
    <a class="vdbs-skip-link" href="#administration-content">Zum Inhalt</a>
    <x-vdbs.portal-header area="Verwaltung" :page-title="$pageTitle" :home-url="$homeUrl" :area-url="$homeUrl" :areas="$areas" :navigation="$navigation" :account="$account" :breadcrumbs="$breadcrumbs ?? []" />
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
    <x-vdbs.portal-footer :home-url="$homeUrl" :links="[['label' => 'Vereinsportal', 'url' => route('my.home')], ['label' => 'Verwaltung', 'url' => $homeUrl]]" />
</body>
</html>

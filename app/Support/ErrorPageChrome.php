<?php

namespace App\Support;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

/**
 * Baut den normalen Portal-Header und -Footer für die Fehlerseiten.
 *
 * Fehlerseiten dürfen nie selbst fehlschlagen. Deshalb:
 * - Ab Status 500 (auch 503/Wartungsmodus) werden weder Sitzung, Anmeldung
 *   noch Datenbank angefasst: es gibt den Gast-Header.
 * - Für alle anderen Fehler wird zuerst der Header mit angemeldeter Person
 *   versucht, bei jedem Fehler danach der Gast-Header.
 * - Scheitert auch das, gibt die Klasse null zurück und das Layout benutzt die
 *   reduzierte Hülle (errors/partials/fallback-*).
 */
final class ErrorPageChrome
{
    private const SERVER_ERROR_FROM = 500;

    private const HEADER_TEMPLATE = <<<'BLADE'
<x-vdbs.portal-header
    area="VDBS Portal"
    :page-title="$pageTitle"
    :home-url="$homeUrl"
    :area-url="$homeUrl"
    :areas="$areas"
    :navigation="$navigation"
    :breadcrumbs="$breadcrumbs"
    :login-url="$loginUrl"
    :account="$account"
/>
BLADE;

    public static function header(?int $status, string $pageTitle): ?string
    {
        $attempts = [false];

        if ($status === null || $status < self::SERVER_ERROR_FROM) {
            array_unshift($attempts, true);
        }

        foreach ($attempts as $withUser) {
            try {
                return Blade::render(
                    self::HEADER_TEMPLATE,
                    self::headerData($withUser, $pageTitle),
                );
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    public static function footer(): ?string
    {
        try {
            return Blade::render('<x-vdbs.portal-footer />');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function headerData(bool $withUser, string $pageTitle): array
    {
        $homeUrl = self::routeUrl('my.home') ?? url('/');
        $loginUrl = self::routeUrl('my.login');

        $user = $withUser ? auth()->user() : null;
        $user = $user instanceof User ? $user : null;

        $areas = $withUser
            ? app(PortalAreaCatalog::class)->switcherAreas($user)
            : [];

        return [
            'pageTitle' => $pageTitle,
            'homeUrl' => $homeUrl,
            'areas' => $areas,
            'navigation' => $user !== null
                ? self::memberNavigation()
                : self::guestNavigation(),
            'breadcrumbs' => [
                ['label' => 'VDBS Portal', 'url' => $homeUrl],
                ['label' => $pageTitle, 'url' => null],
            ],
            'loginUrl' => $user === null ? $loginUrl : null,
            'account' => $user !== null ? self::account($user) : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function guestNavigation(): array
    {
        $children = array_values(array_filter([
            self::item('Registrieren', 'my.registration.create'),
            self::item('Passwort vergessen', 'my.password.request'),
        ]));

        $entry = self::item('Zugang', 'my.login');

        if ($entry === null) {
            return [];
        }

        $entry['children'] = $children;

        return [$entry];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function memberNavigation(): array
    {
        return array_values(array_filter([
            self::item('Start', 'my.home'),
            self::item('Konto', 'my.account'),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private static function account(User $user): array
    {
        $person = $user->person;
        $name = trim(
            ($person?->first_name ?? '').' '.($person?->last_name ?? ''),
        );

        if ($name === '') {
            $name = $user->email;
        }

        $initialSource = $person !== null
            ? trim($person->first_name.' '.$person->last_name)
            : Str::before($user->email, '@');

        $initials = collect(preg_split('/[\s._-]+/u', $initialSource) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        $menu = array_values(array_filter([
            self::item('Mein Profil', 'my.account.profile', icon: 'user'),
            self::item('Kontoeinstellungen', 'my.account.settings', icon: 'settings'),
        ]));

        $logoutUrl = self::routeUrl('my.logout');

        return [
            'name' => $name,
            'handle' => $user->email,
            'initials' => $initials !== '' ? $initials : 'VB',
            'groups' => [$menu],
            'logout' => $logoutUrl !== null
                ? ['label' => 'Abmelden', 'url' => $logoutUrl, 'method' => 'post']
                : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function item(
        string $label,
        string $routeName,
        ?string $icon = null,
    ): ?array {
        $url = self::routeUrl($routeName);

        if ($url === null) {
            return null;
        }

        $item = ['label' => $label, 'url' => $url, 'active' => false];

        if ($icon !== null) {
            $item['icon'] = $icon;
        }

        return $item;
    }

    private static function routeUrl(string $name): ?string
    {
        return Route::has($name) ? route($name) : null;
    }
}

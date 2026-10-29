<?php

namespace App\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use Illuminate\Support\Str;

/**
 * Der Bereichskatalog des Portals: Jede Seite gehört zu genau einem Bereich (PortalAreas).
 *
 * Kopfbereich, Zeile 1: die Bereiche, die der Nutzer sehen darf. Zeile 2: die Seiten des aktuellen Bereichs.
 * Der Bereich wechselt nur durch Klick auf einen Bereich in Zeile 1. Links aus einem Bereich in einen anderen sind
 * Ausnahmen und werden nur nach Abstimmung mit Begründung zugelassen (docs/43_BEREICHE.md).
 *
 * Ein Bereich erscheint, wenn der Nutzer eine der Rollen des Bereichs hat (das Vorstandsmitglied sieht alle) UND mindestens
 * eine Seite des Bereichs sehen darf. Ein Bereich ohne Seiten erscheint nicht. Jede Seite bleibt zusätzlich durch ihre
 * Berechtigung (can) und serverseitig geschützt; der Katalog entscheidet nur, was dargestellt wird.
 *
 * Neue Seite: unter "pages" des richtigen Bereichs eintragen (label, route, match, can). Neuer Bereich: nur über PortalAreas.
 */
final class PortalNavigation
{
    /**
     * @return array<string, array{roles: list<RoleKey>, routes?: list<string>, pages: list<array{label: string, route: string, match: string, can?: string}>}>
     */
    public static function catalog(): array
    {
        return [
            PortalAreas::Start->value => [
                'roles' => [],
                'routes' => ['home'],
                'pages' => [],
            ],
            PortalAreas::Konto->value => [
                'roles' => [],
                'routes' => ['account.*'],
                'pages' => [
                    ['label' => 'Profil', 'route' => 'account.profile', 'match' => 'account.profile*'],
                    ['label' => 'E-Mail', 'route' => 'account.email', 'match' => 'account.email*'],
                    ['label' => 'Passwort', 'route' => 'account.password', 'match' => 'account.password*'],
                    ['label' => 'Sicherheit', 'route' => 'account.security.index', 'match' => 'account.security.*'],
                    ['label' => 'Sitzungen', 'route' => 'account.sessions', 'match' => 'account.sessions*'],
                ],
            ],
            PortalAreas::Vorstand->value => ['roles' => [RoleKey::BoardMember], 'pages' => []],
            PortalAreas::Mitglieder->value => ['roles' => [RoleKey::Member], 'pages' => []],
            PortalAreas::Teamende->value => ['roles' => [RoleKey::Team], 'pages' => []],
            PortalAreas::Schulen->value => ['roles' => [], 'pages' => []],
            PortalAreas::Koordination->value => ['roles' => [RoleKey::Coordination], 'pages' => []],
            PortalAreas::Geschaeftsstelle->value => [
                'roles' => [RoleKey::AdministrationStaff, RoleKey::Administration],
                'pages' => [
                    ['label' => 'Nutzer', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'can' => 'users.view'],
                    ['label' => 'Personen', 'route' => 'admin.people.index', 'match' => 'admin.people.*', 'can' => 'people.view'],
                ],
            ],
            PortalAreas::Bildungsreferenten->value => ['roles' => [RoleKey::EducationCoordination], 'pages' => []],
            PortalAreas::Admins->value => [
                'roles' => [RoleKey::Administration],
                'pages' => [
                    ['label' => 'Protokoll', 'route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'can' => 'audit.view'],
                    ['label' => 'Design-Bibliothek', 'route' => 'design-library.index', 'match' => 'design-library.*'],
                ],
            ],
        ];
    }

    /**
     * Bereiche für Zeile 1 in fester Reihenfolge (Start und Konto stehen nicht in der Leiste: Logo und Avatar-Menü).
     *
     * @return list<PortalAreas>
     */
    public static function visibleAreas(User $user): array
    {
        $held = Capabilities::activeRoleKeys($user);

        return array_values(array_filter(
            PortalAreas::withGroupMark(),
            fn (PortalAreas $area): bool => self::roleAllows($area, $held) && self::pages($area, $user) !== [],
        ));
    }

    /**
     * Seiten eines Bereichs, die der Nutzer sehen darf.
     *
     * @return list<array{label: string, href: string, current: bool}>
     */
    public static function pages(PortalAreas $area, User $user, ?string $routeName = null): array
    {
        $pages = [];

        foreach (self::catalog()[$area->value]['pages'] as $page) {
            if (isset($page['can']) && ! $user->can($page['can'])) {
                continue;
            }

            $pages[] = [
                'label' => $page['label'],
                'href' => route($page['route']),
                'current' => $routeName !== null && Str::is($page['match'], $routeName),
            ];
        }

        return $pages;
    }

    /** Einstieg eines Bereichs: die erste Seite, die der Nutzer sehen darf. */
    public static function home(PortalAreas $area, User $user): ?string
    {
        return self::pages($area, $user)[0]['href'] ?? null;
    }

    /** Zu welchem Bereich gehört eine Seite (Routenname)? Null für Seiten ohne Bereich (Anmeldung, Fehlerseiten). */
    public static function areaForRoute(?string $routeName): ?PortalAreas
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::catalog() as $key => $definition) {
            foreach (self::patterns($definition) as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return PortalAreas::from($key);
                }
            }
        }

        return null;
    }

    /** @return list<string> alle Routenmuster eines Bereichs */
    public static function patterns(array $definition): array
    {
        return array_merge($definition['routes'] ?? [], array_column($definition['pages'], 'match'));
    }

    /** @param  list<string>  $held */
    private static function roleAllows(PortalAreas $area, array $held): bool
    {
        $allowed = array_map(fn (RoleKey $role): string => $role->value, self::catalog()[$area->value]['roles']);
        $allowed[] = RoleKey::BoardMember->value;

        return array_intersect($allowed, $held) !== [];
    }
}

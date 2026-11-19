<?php

namespace App\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\NavigationControl\Support\NavigationControl;
use Illuminate\Http\Request;
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
     * Seiten: label, route, match (Routenmuster für "aktuell" und die Bereichszuordnung; null = nie aktuell, z. B. ein Anker auf
     * einer anderen Seite), can (Berechtigung; ohne can ist die Seite öffentlich oder für alle Angemeldeten), fragment (Anker),
     * children (Untermenü, gleiche Form).
     *
     * @return array<string, array{home: string, roles: list<RoleKey>, routes?: list<string>, pages: list<array<string, mixed>>}>
     */
    public static function catalog(): array
    {
        return self::baseCatalog();
    }

    /**
     * Der Katalog der Bereiche mit ihren Seiten. Bereiche ohne Seiten erscheinen nicht im Kopfbereich.
     *
     * @return array<string, array{home: string, roles: list<RoleKey>, routes?: list<string>, pages: list<array<string, mixed>>}>
     */
    private static function baseCatalog(): array
    {
        return [
            PortalAreas::Start->value => [
                'home' => 'home',
                'roles' => [],
                'routes' => ['home', 'pages.show', 'portal.design-packages'],
                'pages' => [
                    ['label' => 'Über das Portal', 'route' => 'portal.about', 'match' => 'portal.about'],
                    ['label' => 'Zugang zum Portal', 'route' => 'portal.access', 'match' => 'portal.access*'],
                ],
            ],
            PortalAreas::Konto->value => [
                'home' => 'account.home',
                'roles' => [],
                'routes' => ['account.*'],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'account.home', 'match' => 'account.home'],
                    ['label' => 'Profil', 'group' => true, 'unless' => 'school.area', 'route' => 'account.profile', 'children' => [
                        ['description' => 'So sehen andere Ihr Profil.', 'label' => 'Mein Profil', 'route' => 'account.profile', 'match' => 'account.profile'],
                        ['description' => 'Anzeigename, Foto und Kurzvorstellung.', 'label' => 'Profil bearbeiten', 'route' => 'account.profile.edit', 'match' => 'account.profile.edit'],
                        ['description' => 'Wer welche Angabe sehen darf.', 'label' => 'Sichtbarkeit', 'route' => 'account.profile.visibility', 'match' => 'account.profile.visibility*'],
                    ]],
                    ['description' => 'Name, Geburtsdatum und Anschrift.', 'label' => 'Stammdaten', 'route' => 'account.data', 'match' => 'account.data*'],
                    ['label' => 'Anmeldung', 'group' => true, 'route' => 'account.email', 'children' => [
                        ['description' => 'Adresse für Anmeldung und Mitteilungen.', 'label' => 'E-Mail', 'route' => 'account.email', 'match' => 'account.email*'],
                        ['description' => 'Passwort ändern.', 'label' => 'Passwort', 'route' => 'account.password', 'match' => 'account.password*'],
                        ['description' => 'Authentifizierungs-App, E-Mail-Code und Wiederherstellungscodes.', 'label' => 'Zwei-Faktor', 'route' => 'account.security.index', 'match' => 'account.security.*'],
                        ['description' => 'Wo Ihr Konto angemeldet ist.', 'label' => 'Sitzungen', 'route' => 'account.sessions', 'match' => 'account.sessions*'],
                    ]],
                    ['description' => 'Systeme, bei denen Sie sich über das Portal angemeldet haben.', 'label' => 'Verbundene Anwendungen', 'route' => 'account.apps', 'match' => 'account.apps*'],
                    ['description' => 'Ihre Rollen und die Bereiche, die Ihnen offenstehen.', 'label' => 'Rollen & Bereiche', 'route' => 'account.roles', 'match' => 'account.roles'],
                    ['label' => 'Einstellungen', 'group' => true, 'route' => 'account.notifications', 'children' => [
                        ['description' => 'Welche E-Mails Sie vom Portal bekommen.', 'label' => 'Benachrichtigungen', 'route' => 'account.notifications', 'match' => 'account.notifications*'],
                        ['description' => 'Hell, dunkel oder wie das Gerät.', 'label' => 'Darstellung', 'route' => 'account.appearance', 'match' => 'account.appearance'],
                    ]],
                    ['description' => 'Konto löschen.', 'label' => 'Daten & Löschung', 'route' => 'account.deletion', 'match' => 'account.deletion*'],
                ],
            ],
            PortalAreas::Vorstand->value => ['home' => 'board.overview', 'routes' => ['board.overview'], 'roles' => [RoleKey::BoardMember], 'pages' => []],
            PortalAreas::Mitglieder->value => ['home' => 'member.home', 'routes' => ['member.home'], 'roles' => [RoleKey::Member], 'pages' => []],
            PortalAreas::Teamende->value => ['home' => 'team.home', 'routes' => ['team.home'], 'roles' => [RoleKey::Team], 'pages' => []],
            PortalAreas::Mitarbeitende->value => ['home' => 'employee.home', 'routes' => ['employee.home'], 'roles' => [RoleKey::Employee, RoleKey::ManagingDirector, RoleKey::Treasury], 'pages' => []],
            PortalAreas::Schulen->value => ['home' => 'school.home', 'routes' => ['school.home'], 'roles' => [RoleKey::School], 'pages' => []],
            PortalAreas::Koordination->value => ['home' => 'coordination.home', 'routes' => ['coordination.home'], 'roles' => [RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::EducationCoordination], 'pages' => []],
            PortalAreas::Geschaeftsstelle->value => [
                'home' => 'office.home',
                'roles' => [RoleKey::AdministrationStaff, RoleKey::Administration, RoleKey::ManagingDirector, RoleKey::Treasury],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'office.home', 'match' => 'office.home'],
                    ['label' => 'Personen & Zugang', 'group' => true, 'route' => 'admin.users.index', 'children' => [
                        ['description' => 'Konten, Rollen und Sicherheit.', 'label' => 'Nutzer', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'can' => 'users.view'],
                        ['description' => 'Anträge auf Zugang zum Portal.', 'label' => 'Zugangsanträge', 'route' => 'access-requests.index', 'match' => 'access-requests.*', 'can' => 'access_requests.view'],
                        ['description' => 'Personen und ihre Kontaktdaten.', 'label' => 'Personen', 'route' => 'admin.people.index', 'match' => 'admin.people.*', 'can' => 'people.view'],
                    ]],
                    ['label' => 'Inhalte', 'group' => true, 'route' => 'pages.admin.index', 'children' => [
                        ['description' => 'Rechtstexte und andere Seiten.', 'label' => 'Seiten', 'route' => 'pages.admin.index', 'match' => 'pages.admin.*', 'can' => 'pages.manage'],
                        ['description' => 'Wichtige Links je Rolle für das Profilmenü.', 'label' => 'Schnellzugriffe', 'route' => 'role-links.edit', 'match' => 'role-links.*', 'can' => 'role_links.manage'],
                        ['description' => 'Texte der automatischen E-Mails (Passwort, Einladung, Zugangsanträge), Mail-Design und Bilder.', 'label' => 'Systemmails', 'route' => 'mailbuilder.system.index', 'match' => 'mailbuilder.*', 'can' => 'mail_system.manage'],
                    ]],
                ],
            ],
            PortalAreas::Bildungsreferenten->value => ['home' => 'education.overview', 'routes' => ['education.overview'], 'roles' => [RoleKey::EducationCoordination], 'pages' => []],
            PortalAreas::Admins->value => [
                'home' => 'admins.home',
                'roles' => [RoleKey::Administration],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'admins.home', 'match' => 'admins.home'],
                    ['description' => 'Das Protokoll sicherheitsrelevanter Vorgänge.', 'label' => 'Protokoll', 'route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'can' => 'audit.view'],
                    ['label' => 'System', 'group' => true, 'route' => 'system.index', 'children' => [
                        ['description' => 'Zustand des Portals: Prüfungen, Versionen, Planer.', 'label' => 'Zustand', 'route' => 'system.index', 'match' => 'system.index', 'can' => 'system.manage'],
                        ['description' => 'Aufgaben ausführen: Cache leeren, Migrationen, geplante Aufgaben.', 'label' => 'Aufgaben', 'route' => 'system.tasks', 'match' => 'system.tasks*', 'can' => 'system.manage'],
                        ['description' => 'Fehler und Warnungen der Anwendung aus der Protokolldatei.', 'label' => 'Fehlerprotokoll', 'route' => 'system.logs', 'match' => 'system.logs', 'can' => 'system.manage'],
                        ['description' => 'Fehlgeschlagene Aufträge erneut versuchen oder verwerfen.', 'label' => 'Warteschlange', 'route' => 'system.queue', 'match' => 'system.queue*', 'can' => 'system.manage'],
                        ['description' => 'Fristen, E-Mail und Betrieb zum Nachlesen, Testmail senden.', 'label' => 'Einstellungen', 'route' => 'system.settings', 'match' => 'system.settings*', 'can' => 'system.manage'],
                    ]],
                    ['description' => 'Nextcloud, Moodle und andere Systeme, die sich über das Portal anmelden.', 'label' => 'Anbindungen', 'route' => 'admin.oauth.index', 'match' => 'admin.oauth.*', 'can' => 'oauth.manage'],
                    ['label' => 'Web-Control', 'group' => true, 'route' => 'navigation.edit', 'children' => [
                        ['description' => 'Reihenfolge, Name und Sichtbarkeit der Bereiche im Kopfmenü.', 'label' => 'Bereiche', 'route' => 'navigation.edit', 'match' => ['navigation.edit', 'navigation.area', 'navigation.area.*'], 'can' => 'navigation.manage'],
                        ['description' => 'Header der Bereiche: Anzahl, Titel und Position der Seiten und Links.', 'label' => 'Header', 'route' => 'navigation.headers', 'match' => ['navigation.headers', 'navigation.header', 'navigation.header.*'], 'can' => 'navigation.manage'],
                    ]],
                    ['description' => 'Komponenten und Muster des Designsystems.', 'label' => 'Design-Bibliothek', 'route' => 'design-library.index', 'match' => 'design-library.*', 'can' => 'design.view'],
                ],
            ],
        ];
    }

    /**
     * Bereiche für Zeile 1 in fester Reihenfolge (Start und Konto stehen nicht in der Leiste: Logo und Avatar-Menü).
     *
     * @return list<PortalAreas>
     */
    public static function visibleAreas(User $user, string $context = 'header'): array
    {
        $held = Capabilities::activeRoleKeys($user);

        $areas = array_values(array_filter(
            PortalAreas::withGroupMark(),
            fn (PortalAreas $area): bool => NavigationControl::areaEnabled($area, $context) && NavigationControl::rolesAllow($area, $held) && self::roleAllows($area, $held) && self::pages($area, $user) !== [],
        ));

        // Reihenfolge laut Web-Control (Standard: wie im Katalog)
        $order = array_flip(array_map(fn (PortalAreas $area): string => $area->value, $areas));
        usort($areas, fn (PortalAreas $a, PortalAreas $b): int => NavigationControl::areaSort($a, $order[$a->value]) <=> NavigationControl::areaSort($b, $order[$b->value]));

        return $areas;
    }

    /**
     * Seiten eines Bereichs, die der Nutzer sehen darf (ohne Nutzer: nur Seiten ohne Berechtigung, also die öffentlichen).
     *
     * @return list<array<string, mixed>>
     */
    public static function pages(PortalAreas $area, ?User $user, ?string $routeName = null): array
    {
        return NavigationControl::decorate($area, self::visiblePages(self::catalog()[$area->value]['pages'], $user, $routeName, $area));
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    private static function visiblePages(array $pages, ?User $user, ?string $routeName, PortalAreas $area): array
    {
        $visible = [];

        foreach ($pages as $page) {
            // Im Web-Control (Admins › Navigation) ausgeblendete Seiten erscheinen nicht; Gruppen entscheiden über ihre Seiten
            if (! ($page['group'] ?? false) && ! NavigationControl::pinned($page) && ! NavigationControl::pageEnabled($area, $page)) {
                continue;
            }

            $children = self::visiblePages($page['children'] ?? [], $user, $routeName, $area);
            $group = (bool) ($page['group'] ?? false);

            // Eine Gruppe ist nur ein Sammelpunkt im Menü: sie erscheint, wenn der Nutzer mindestens eine Seite darin sehen darf, und führt zur ersten davon.
            if ($group) {
                if ($children === []) {
                    continue;
                }
            } elseif (isset($page['can']) && ($user === null || ! $user->can($page['can']))) {
                continue;
            }

            // „any_role“: Seite nur für Konten mit einer dieser Rollen
            if (isset($page['any_role']) && ($user === null || array_intersect($page['any_role'], Capabilities::activeRoleKeys($user)) === [])) {
                continue;
            }

            // „unless“: Seite entfällt für Nutzer mit dieser Berechtigung (z. B. kein Profil für Schulkonten)
            if (isset($page['unless']) && $user !== null && $user->can($page['unless'])) {
                continue;
            }

            $current = ! $group && $routeName !== null && isset($page['match']) && Str::is($page['match'], $routeName);

            $visible[] = [
                'key' => NavigationControl::pageKey($page),
                'label' => $page['label'],
                'description' => $page['description'] ?? null,
                'icon' => $page['icon'] ?? self::iconFor((string) $page['route']),
                'href' => $group ? $children[0]['href'] : route($page['route'], $page['params'] ?? []).(isset($page['fragment']) ? '#'.$page['fragment'] : ''),
                'current' => $current,
                'active' => $current || array_filter($children, fn (array $child): bool => $child['active']) !== [],
                'group' => $group,
                'children' => $children,
            ];
        }

        return $visible;
    }

    /** Icon einer Seite für die Karten auf den Startseiten der Bereiche (nach Routenname; null: das Icon des Bereichs). */
    public static function iconFor(string $routeName): ?string
    {
        foreach ([
            'membership.*' => 'users', 'onboarding.*' => 'clipboard-list', 'team-onboarding.*' => 'clipboard-list', 'mailings.*' => 'mail', 'mailbuilder.*' => 'mail', 'board.meetings.*' => 'calendar', 'board.resolutions.*' => 'landmark',
            'board.reports*' => 'file', 'member.membership' => 'users', 'forms.*' => 'file', 'forms-office.*' => 'file', 'partners.*' => 'school',
            'team.*' => 'user', 'school.*' => 'school', 'admin.users.*' => 'users', 'access-requests.*' => 'key-round',
            'admin.people.*' => 'user', 'contacts.*' => 'mail', 'articles.*' => 'book-open', 'pages.*' => 'file', 'contact-admin.*' => 'mail', 'faq-admin.*' => 'circle-help', 'role-links.*' => 'link', 'coordinations.*' => 'map-pin', 'seminars.*' => 'calendar', 'school.seminars.*' => 'calendar', 'support.*' => 'ticket', 'tickets-admin.*' => 'ticket', 'system.*' => 'settings',
            'admin.audit.*' => 'shield', 'admin.oauth.*' => 'link', 'design-library.*' => 'layout-dashboard', 'tasks.*' => 'square-kanban', 'account.*' => 'user', 'education.*' => 'graduation-cap',
        ] as $pattern => $icon) {
            if (Str::is($pattern, $routeName)) {
                return $icon;
            }
        }

        return null;
    }

    /** Startseite eines Bereichs: erreichbar über den Bereichstitel im Kopfbereich und als erste Brotkrume. */
    public static function home(PortalAreas $area, ?User $user = null): string
    {
        return route(self::catalog()[$area->value]['home']);
    }

    /** Darf der Nutzer den Bereich betreten (Rolle des Bereichs oder Vorstand)? Für die Übersichtsseiten der Bereiche. */
    public static function canEnter(PortalAreas $area, ?User $user): bool
    {
        return $user !== null && self::roleAllows($area, Capabilities::activeRoleKeys($user));
    }

    /**
     * Brotkrumen mit der Startseite des Bereichs als erstem Eintrag. Die Seiten liefern ihre Krumen wie bisher (Start, Bereich, …); hier werden
     * „Start“, der Bereichsname und „Verwaltung“ am Anfang durch die Startseite des Bereichs ersetzt. Seiten ohne Bereich bleiben unverändert.
     *
     * @param  list<array{label: string, href?: ?string}>  $items
     * @return list<array{label: string, href?: ?string}>
     */
    public static function breadcrumb(array $items, ?string $routeName, ?PortalAreas $area = null): array
    {
        $area ??= self::areaForRoute($routeName);

        if ($area === null || $items === []) {
            return $items;
        }

        $catalog = self::catalog()[$area->value];
        $first = ['label' => $area->label(), 'href' => route($catalog['home'])];

        if ($routeName === $catalog['home']) {
            return [['label' => $area->label()]];
        }

        if (($items[0]['label'] ?? null) === 'Start') {
            array_shift($items);
        }

        if (in_array($items[0]['label'] ?? null, [$area->label(), 'Verwaltung'], true)) {
            array_shift($items);
        }

        return [$first, ...array_values($items)];
    }

    /** Zu welchem Bereich gehört die aktuelle Seite? Null für Seiten ohne Bereich (Anmeldung, Fehlerseiten). */
    public static function areaForRequest(?Request $request): ?PortalAreas
    {
        return self::areaForRoute($request?->route()?->getName());
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

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string> alle Routenmuster eines Bereichs (auch die der Untermenüs)
     */
    public static function patterns(array $definition): array
    {
        $patterns = $definition['routes'] ?? [];

        $collect = function (array $pages) use (&$patterns, &$collect): void {
            foreach ($pages as $page) {
                if (isset($page['match']) && empty($page['dynamic'])) {
                    foreach ((array) $page['match'] as $match) {
                        $patterns[] = $match;
                    }
                }

                $collect($page['children'] ?? []);
            }
        };

        $collect($definition['pages']);

        return $patterns;
    }

    /** @param  list<string>  $held */
    private static function roleAllows(PortalAreas $area, array $held): bool
    {
        $allowed = array_map(fn (RoleKey $role): string => $role->value, self::catalog()[$area->value]['roles']);
        $allowed[] = RoleKey::BoardMember->value;

        return array_intersect($allowed, $held) !== [];
    }
}

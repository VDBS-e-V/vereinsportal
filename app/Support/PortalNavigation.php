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
     * Seiten: label, route, match (Routenmuster für "aktuell" und die Bereichszuordnung; null = nie aktuell, z. B. ein Anker auf
     * einer anderen Seite), can (Berechtigung; ohne can ist die Seite öffentlich oder für alle Angemeldeten), fragment (Anker),
     * children (Untermenü, gleiche Form).
     *
     * @return array<string, array{roles: list<RoleKey>, routes?: list<string>, pages: list<array<string, mixed>>}>
     */
    public static function catalog(): array
    {
        return [
            PortalAreas::Start->value => [
                'home' => 'home',
                'roles' => [],
                'routes' => ['home', 'join.*', 'articles.public.*', 'pages.show', 'portal.design-packages'],
                'pages' => [
                    ['label' => 'Über das Portal', 'route' => 'portal.about', 'match' => 'portal.about', 'children' => [
                        ['label' => 'Zugang zum Portal', 'route' => 'portal.access', 'match' => 'portal.access*'],
                        ['label' => 'FAQ', 'route' => 'portal.about', 'fragment' => 'portal-faq', 'match' => null],
                    ]],
                    ['label' => 'Veranstaltungen', 'route' => 'events.public.index', 'match' => 'events.public.*'],
                    ['label' => 'Mitglied werden', 'route' => 'join.apply', 'match' => 'join.apply*'],
                    ['label' => 'Kontakt', 'route' => 'portal.contact', 'match' => 'portal.contact*'],
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
                    ['description' => 'Ihre Rollen und die Bereiche, die Ihnen offenstehen.', 'label' => 'Rollen & Bereiche', 'route' => 'account.roles', 'match' => 'account.roles'],
                    ['label' => 'Einstellungen', 'group' => true, 'route' => 'account.notifications', 'children' => [
                        ['description' => 'Welche E-Mails Sie vom Portal bekommen.', 'label' => 'Benachrichtigungen', 'route' => 'account.notifications', 'match' => 'account.notifications*'],
                        ['description' => 'Hell, dunkel oder wie das Gerät.', 'label' => 'Darstellung', 'route' => 'account.appearance', 'match' => 'account.appearance'],
                    ]],
                    ['description' => 'Konto löschen.', 'label' => 'Daten & Löschung', 'route' => 'account.deletion', 'match' => 'account.deletion*'],
                ],
            ],
            PortalAreas::Vorstand->value => [
                'home' => 'board.overview',
                'roles' => [RoleKey::BoardMember],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'board.overview', 'match' => 'board.overview', 'can' => 'memberships.view'],
                    ['label' => 'Mitgliedschaften', 'route' => 'membership.index', 'match' => 'membership.*', 'can' => 'memberships.view', 'children' => [
                        ['label' => 'Willkommenspaket', 'route' => 'onboarding.member', 'match' => 'onboarding.member*', 'can' => 'memberships.view'],
                    ]],
                    ['label' => 'Berichte', 'route' => 'board.reports', 'match' => 'board.reports*', 'can' => 'reports.view'],
                ],
            ],
            PortalAreas::Mitglieder->value => [
                'home' => 'member.home',
                'roles' => [RoleKey::Member],
                'routes' => ['member.documents.*'],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'member.home', 'match' => 'member.home', 'can' => 'member.area'],
                    ['description' => 'Status, Beitritt und Austritt Ihrer Mitgliedschaft.', 'label' => 'Mitgliedschaft', 'route' => 'member.membership', 'match' => 'member.membership', 'can' => 'member.area'],
                ],
            ],
            PortalAreas::Teamende->value => [
                'home' => 'team.home',
                'roles' => [RoleKey::Team],
                'routes' => ['onboarding.documents.*'],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'team.home', 'match' => 'team.home', 'can' => 'seminars.apply'],
                    ['description' => 'Seminare mit Stichtag, Ihr Interesse und Ihr Team.', 'label' => 'Seminare', 'route' => 'seminar-applications.index', 'match' => 'seminar-applications.*', 'can' => 'seminars.apply'],
                    ['description' => 'Ihre Angaben für die Teamzusammenstellung.', 'label' => 'Profil', 'route' => 'team-profile.edit', 'match' => 'team-profile.*', 'can' => 'seminars.apply'],
                    ['description' => 'Ihre Schritte der Einarbeitung.', 'label' => 'Einarbeitung', 'route' => 'team-self.index', 'match' => 'team-self.*', 'can' => 'team.onboarding'],
                ],
            ],
            PortalAreas::Schulen->value => [
                'home' => 'school.home',
                'roles' => [RoleKey::School],
                'routes' => ['school.quotes.*', 'school.seminars.*'],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'school.home', 'match' => 'school.home', 'can' => 'school.area'],
                    ['label' => 'Seminar anfragen', 'route' => 'school.requests.create', 'match' => 'school.requests.*', 'can' => 'school.area'],
                    ['label' => 'Veranstaltungen', 'route' => 'school.events.index', 'match' => 'school.events.*', 'can' => 'school.area'],
                ],
            ],
            PortalAreas::Koordination->value => [
                'home' => 'coordination.home',
                'roles' => [RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::EducationCoordination],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'coordination.home', 'match' => 'coordination.home'],
                    ['description' => 'Partnerschulen, Ansprechpersonen und Anfragen.', 'label' => 'Schulen', 'route' => 'partners.index', 'match' => 'partners.*', 'can' => 'partners.view'],
                    ['description' => 'Teamende und ihre Einarbeitung.', 'label' => 'Teamende', 'route' => 'team-onboarding.index', 'match' => 'team-onboarding.*', 'can' => 'team.view'],
                    ['description' => 'Seminare planen, Bewerbungen sichten, Teams zusammenstellen.', 'label' => 'Seminare', 'route' => 'seminars.index', 'match' => 'seminars.*', 'can' => 'seminars.view'],
                    ['description' => 'Veranstaltungen, Anmeldungen und Nachrichten.', 'label' => 'Veranstaltungen', 'route' => 'events.admin.index', 'match' => 'events.admin.*', 'can' => 'events.view'],
                ],
            ],
            PortalAreas::Geschaeftsstelle->value => [
                'home' => 'office.home',
                'roles' => [RoleKey::AdministrationStaff, RoleKey::Administration],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'office.home', 'match' => 'office.home'],
                    ['label' => 'Personen & Zugang', 'group' => true, 'route' => 'admin.users.index', 'children' => [
                        ['description' => 'Konten, Rollen und Sicherheit.', 'label' => 'Nutzer', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'can' => 'users.view'],
                        ['description' => 'Anträge auf Zugang zum Portal.', 'label' => 'Zugangsanträge', 'route' => 'access-requests.index', 'match' => 'access-requests.*', 'can' => 'access_requests.view'],
                        ['description' => 'Personen und ihre Kontaktdaten.', 'label' => 'Personen', 'route' => 'admin.people.index', 'match' => 'admin.people.*', 'can' => 'people.view'],
                    ]],
                    ['label' => 'Inhalte', 'group' => true, 'route' => 'articles.admin.index', 'children' => [
                        ['description' => 'Artikel für Startseite und Artikelliste.', 'label' => 'Artikel', 'route' => 'articles.admin.index', 'match' => 'articles.admin.*', 'can' => 'articles.manage'],
                        ['description' => 'Rechtstexte und andere Seiten.', 'label' => 'Seiten', 'route' => 'pages.admin.index', 'match' => 'pages.admin.*', 'can' => 'pages.manage'],
                    ]],
                    ['label' => 'Preise & Angebote', 'group' => true, 'route' => 'seminar-quotes.index', 'children' => [
                        ['description' => 'Kostenvoranschläge für Seminare.', 'label' => 'Kostenvoranschläge', 'route' => 'seminar-quotes.index', 'match' => 'seminar-quotes.*', 'can' => 'seminars.view'],
                        ['description' => 'Preislisten für Kostenvoranschläge.', 'label' => 'Preise', 'route' => 'seminar-prices.index', 'match' => 'seminar-prices.*', 'can' => 'seminars.view'],
                        ['description' => 'Textbausteine für Kostenvoranschläge.', 'label' => 'Texte', 'route' => 'seminar-texts.index', 'match' => 'seminar-texts.*', 'can' => 'seminars.view'],
                    ]],
                ],
            ],
            PortalAreas::Bildungsreferenten->value => [
                'home' => 'education.overview',
                'roles' => [RoleKey::EducationCoordination],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'education.overview', 'match' => 'education.*', 'can' => 'education.view'],
                ],
            ],
            PortalAreas::Admins->value => [
                'home' => 'admins.home',
                'roles' => [RoleKey::Administration],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'admins.home', 'match' => 'admins.home'],
                    ['description' => 'Das Protokoll sicherheitsrelevanter Vorgänge.', 'label' => 'Protokoll', 'route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'can' => 'audit.view'],
                    ['description' => 'Zustand des Portals: Prüfungen, Versionen, Planer.', 'label' => 'System', 'route' => 'system.index', 'match' => 'system.index', 'can' => 'system.manage'],
                    ['description' => 'Aufgaben ausführen: Cache leeren, Migrationen, geplante Aufgaben.', 'label' => 'Aufgaben', 'route' => 'system.tasks', 'match' => 'system.tasks*', 'can' => 'system.manage'],
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
    public static function visibleAreas(User $user): array
    {
        $held = Capabilities::activeRoleKeys($user);

        return array_values(array_filter(
            PortalAreas::withGroupMark(),
            fn (PortalAreas $area): bool => self::roleAllows($area, $held) && self::pages($area, $user) !== [],
        ));
    }

    /**
     * Seiten eines Bereichs, die der Nutzer sehen darf (ohne Nutzer: nur Seiten ohne Berechtigung, also die öffentlichen).
     *
     * @return list<array{label: string, href: string, current: bool, children: list<array<string, mixed>>}>
     */
    public static function pages(PortalAreas $area, ?User $user, ?string $routeName = null): array
    {
        return self::visiblePages(self::catalog()[$area->value]['pages'], $user, $routeName);
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array{label: string, href: string, current: bool, children: list<array<string, mixed>>}>
     */
    private static function visiblePages(array $pages, ?User $user, ?string $routeName): array
    {
        $visible = [];

        foreach ($pages as $page) {
            $children = self::visiblePages($page['children'] ?? [], $user, $routeName);
            $group = (bool) ($page['group'] ?? false);

            // Eine Gruppe ist nur ein Sammelpunkt im Menü: sie erscheint, wenn der Nutzer mindestens eine Seite darin sehen darf, und führt zur ersten davon.
            if ($group) {
                if ($children === []) {
                    continue;
                }
            } elseif (isset($page['can']) && ($user === null || ! $user->can($page['can']))) {
                continue;
            }

            // „unless“: Seite entfällt für Nutzer mit dieser Berechtigung (z. B. kein Profil für Schulkonten)
            if (isset($page['unless']) && $user !== null && $user->can($page['unless'])) {
                continue;
            }

            $current = ! $group && $routeName !== null && isset($page['match']) && Str::is($page['match'], $routeName);

            $visible[] = [
                'label' => $page['label'],
                'description' => $page['description'] ?? null,
                'href' => $group ? $children[0]['href'] : route($page['route']).(isset($page['fragment']) ? '#'.$page['fragment'] : ''),
                'current' => $current,
                'active' => $current || array_filter($children, fn (array $child): bool => $child['active']) !== [],
                'group' => $group,
                'children' => $children,
            ];
        }

        return $visible;
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
    public static function breadcrumb(array $items, ?string $routeName): array
    {
        $area = self::areaForRoute($routeName);

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
                if (isset($page['match'])) {
                    $patterns[] = $page['match'];
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

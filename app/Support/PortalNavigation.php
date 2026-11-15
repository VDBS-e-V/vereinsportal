<?php

namespace App\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskProject;
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
     * @return array<string, array{roles: list<RoleKey>, routes?: list<string>, pages: list<array<string, mixed>>}>
     */
    public static function catalog(): array
    {
        $catalog = self::baseCatalog();

        // Aufgaben sind kein eigener Bereich: Jeder Bereich, in dem Aufgaben genutzt werden, hat den Menüpunkt „Aufgaben“ mit den Projekten dieses Bereichs
        foreach (self::taskAreas() as $area) {
            $catalog[$area->value]['pages'][] = [
                'description' => 'Projekte dieses Bereichs mit Kanban-Tafel und Liste.', 'label' => 'Aufgaben', 'route' => 'tasks.area', 'params' => ['area' => $area->value],
                'match' => ['tasks.area', 'tasks.projects.show', 'tasks.projects.edit', 'tasks.projects.update', 'tasks.templates.*', 'tasks.show', 'tasks.update', 'tasks.store', 'tasks.bulk', 'tasks.move', 'tasks.destroy', 'tasks.comments.*', 'tasks.attachments.*', 'tasks.subtasks.*'],
                'dynamic' => true, 'can' => 'tasks.use',
                // „Aufgaben“ erscheint nur für die eigenen Rollen des Bereichs (das Vorstandsmitglied sieht jeden Bereich, bekäme sonst auch leere Aufgabenseiten fremder Bereiche)
                'any_role' => array_map(fn (RoleKey $role): string => $role->value, $catalog[$area->value]['roles']),
            ];
        }

        // Die allgemeine Verwaltung der Aufgabenprojekte gehört zur Geschäftsstelle
        $catalog[PortalAreas::Geschaeftsstelle->value]['pages'][] = [
            'description' => 'Alle Projekte anlegen, archivieren und löschen, Bereich und Mitglieder festlegen.', 'label' => 'Aufgaben verwalten', 'route' => 'tasks.index',
            'match' => ['tasks.index', 'tasks.projects.create', 'tasks.projects.store', 'tasks.projects.archive', 'tasks.projects.destroy'], 'can' => 'tasks.admin',
        ];

        return $catalog;
    }

    /**
     * Bereiche, in denen das Aufgabensystem genutzt wird (Rollen mit der Berechtigung tasks.use).
     *
     * @return list<PortalAreas>
     */
    public static function taskAreas(): array
    {
        return [PortalAreas::Vorstand, PortalAreas::Mitarbeitende, PortalAreas::Koordination, PortalAreas::Geschaeftsstelle, PortalAreas::Bildungsreferenten, PortalAreas::Admins];
    }

    /** Aufgaben-Routen, die zu genau einem Bereich gehören (alle anderen tasks.*-Routen gehören zum Bereich ihres Projekts, siehe areaForRequest). */
    private const FIXED_TASK_ROUTES = ['tasks.mine', 'tasks.index', 'tasks.projects.create', 'tasks.projects.store', 'tasks.projects.archive', 'tasks.projects.destroy'];

    /**
     * Der Katalog ohne die Aufgaben-Seiten, die für jeden Bereich gleich gebaut werden.
     *
     * @return array<string, array{roles: list<RoleKey>, routes?: list<string>, pages: list<array<string, mixed>>}>
     */
    private static function baseCatalog(): array
    {
        return [
            PortalAreas::Start->value => [
                'home' => 'home',
                'roles' => [],
                'routes' => ['home', 'join.*', 'events.public.*', 'articles.public.*', 'pages.show', 'portal.design-packages'],
                'pages' => [
                    ['label' => 'Über das Portal', 'route' => 'portal.about', 'match' => 'portal.about'],
                    ['label' => 'Zugang zum Portal', 'route' => 'portal.access', 'match' => 'portal.access*'],
                    ['label' => 'FAQ', 'route' => 'portal.faq', 'match' => 'portal.faq'],
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
                    ['description' => 'Alle Aufgaben, die Ihnen zugewiesen sind, aus allen Projekten.', 'label' => 'Meine Aufgaben', 'route' => 'tasks.mine', 'match' => 'tasks.mine', 'can' => 'tasks.use'],
                    ['description' => 'Ihre Anfragen und Meldungen an das Portal-Team, mit Verlauf und Nachrichten.', 'label' => 'Meine Tickets', 'route' => 'support.mine', 'match' => 'support.*'],
                    ['description' => 'Ihre Anmeldungen zu Veranstaltungen mit Ticketcode.', 'label' => 'Meine Anmeldungen', 'route' => 'account.tickets', 'match' => 'account.tickets'],
                    ['description' => 'Systeme, bei denen Sie sich über das Portal angemeldet haben.', 'label' => 'Verbundene Anwendungen', 'route' => 'account.apps', 'match' => 'account.apps*'],
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
                    ['description' => 'E-Mails an Mitglieder und andere Gruppen, frei oder mit Vorlage.', 'label' => 'Rundschreiben', 'route' => 'mailings.board.index', 'match' => 'mailings.board.*', 'can' => 'mailings.members'],
                    ['description' => 'Vorstandssitzungen planen, Tagesordnung, Anwesenheit und Protokoll, mit Kalender-Abo.', 'label' => 'Sitzungen', 'route' => 'board.meetings.index', 'match' => 'board.meetings.*', 'can' => 'board.meetings'],
                    ['description' => 'Alle Beschlüsse des Vorstands, nummeriert und durchsuchbar.', 'label' => 'Beschlüsse', 'route' => 'board.resolutions.index', 'match' => 'board.resolutions.*', 'can' => 'board.meetings'],
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
                    ['description' => 'Auslagen und Fahrtkosten für Ihr Engagement erstatten lassen.', 'label' => 'Anträge', 'route' => 'member-forms.index', 'match' => 'member-forms.*', 'can' => 'forms.submit_member'],
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
                    ['description' => 'Auslagen und Fahrtkosten zu Ihren Seminaren erstatten lassen.', 'label' => 'Anträge', 'route' => 'team-forms.index', 'match' => 'team-forms.*', 'can' => 'forms.submit_team'],
                    ['description' => 'Steuernummer, Umsatzsteuer und Bankverbindung für Ihre Verträge.', 'label' => 'Vertragsdaten', 'route' => 'contractor.edit', 'match' => 'contractor.*', 'can' => 'seminars.apply'],
                ],
            ],
            PortalAreas::Mitarbeitende->value => [
                'home' => 'employee.home',
                'roles' => [RoleKey::Employee, RoleKey::ManagingDirector, RoleKey::Treasury],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'employee.home', 'match' => 'employee.home', 'can' => 'employee.area'],
                    ['description' => 'Kostenrückerstattung, Fahrtkosten und Bestellungen.', 'label' => 'Anträge', 'route' => 'employee-forms.index', 'match' => 'employee-forms.*', 'can' => 'forms.submit'],
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
                'routes' => ['contracts.show', 'contracts.edit', 'contracts.print'],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'coordination.home', 'match' => 'coordination.home'],
                    ['description' => 'Partnerschulen, Ansprechpersonen und Anfragen.', 'label' => 'Schulen', 'route' => 'partners.index', 'match' => 'partners.*', 'can' => 'partners.view'],
                    ['description' => 'Teamende und ihre Einarbeitung.', 'label' => 'Teamende', 'route' => 'team-onboarding.index', 'match' => 'team-onboarding.*', 'can' => 'team.view'],
                    ['description' => 'Seminare planen, Bewerbungen sichten, Teams zusammenstellen.', 'label' => 'Seminare', 'route' => 'seminars.index', 'match' => 'seminars.*', 'can' => 'seminars.view'],
                    ['description' => 'Abgeschlossene Seminare mit Kennzahlen und CSV-Export.', 'label' => 'Statistik', 'route' => 'seminar-stats.index', 'match' => 'seminar-stats.*', 'can' => 'seminars.view'],
                    ['description' => 'Fragebögen für Schulklassen, Lehrkräfte und Teamende bearbeiten.', 'label' => 'Feedbackformulare', 'route' => 'feedback-forms.index', 'match' => 'feedback-forms.*', 'can' => 'feedback.manage'],
                    ['description' => 'Rückmeldungen zu Seminaren auswerten und vergleichen.', 'label' => 'Feedback-Auswertung', 'route' => 'feedback-eval.index', 'match' => 'feedback-eval.*', 'can' => 'feedback.view'],
                    ['description' => 'E-Mails an Teamende zu anstehenden Seminaren.', 'label' => 'Mitteilungen an Teamende', 'route' => 'mailings.team.index', 'match' => 'mailings.team.*', 'can' => 'mailings.team'],
                    ['description' => 'E-Mails an Teamende, Schulen, Koordination und Bildungsreferenten.', 'label' => 'Rundschreiben', 'route' => 'mailings.coordination.index', 'match' => 'mailings.coordination.*', 'can' => 'mailings.send_limited'],
                    ['description' => 'Vertragstext, Vereinsdaten und Verträge der Seminare.', 'label' => 'Verträge', 'route' => 'contracts.admin.index', 'match' => 'contracts.admin.*', 'can' => 'contracts.manage'],
                    ['description' => 'Word-Vorlagen mit Platzhaltern für Schreiben an Schulen und Teamende.', 'label' => 'Briefvorlagen', 'route' => 'letters.index', 'match' => 'letters.*', 'can' => 'letters.use'],
                    ['description' => 'Veranstaltungen, Anmeldungen und Nachrichten.', 'label' => 'Veranstaltungen', 'route' => 'events.admin.index', 'match' => 'events.admin.*', 'can' => 'events.view'],
                ],
            ],
            PortalAreas::Geschaeftsstelle->value => [
                'home' => 'office.home',
                'roles' => [RoleKey::AdministrationStaff, RoleKey::Administration, RoleKey::ManagingDirector, RoleKey::Treasury],
                'routes' => ['forms-office.*'],
                'pages' => [
                    ['label' => 'Übersicht', 'route' => 'office.home', 'match' => 'office.home'],
                    ['label' => 'Personen & Zugang', 'group' => true, 'route' => 'admin.users.index', 'children' => [
                        ['description' => 'Konten, Rollen und Sicherheit.', 'label' => 'Nutzer', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'can' => 'users.view'],
                        ['description' => 'Anträge auf Zugang zum Portal.', 'label' => 'Zugangsanträge', 'route' => 'access-requests.index', 'match' => 'access-requests.*', 'can' => 'access_requests.view'],
                        ['description' => 'Personen und ihre Kontaktdaten.', 'label' => 'Personen', 'route' => 'admin.people.index', 'match' => 'admin.people.*', 'can' => 'people.view'],
                        ['description' => 'Importierte Mailingliste und persönliche Zugänge für Bewerbungen.', 'label' => 'Teamkontakte', 'route' => 'contacts.index', 'match' => 'contacts.*', 'can' => 'contacts.manage'],
                    ]],
                    ['description' => 'Anträge prüfen, freigeben und die Sammelliste für die Auszahlung erstellen.', 'label' => 'Anträge', 'route' => 'forms-office.index', 'match' => 'forms-office.*', 'can' => 'forms.view'],
                    ['description' => 'Anfragen und Meldungen aller Personen: zuweisen, bearbeiten, im Ticket antworten.', 'label' => 'Tickets', 'route' => 'tickets-admin.index', 'match' => 'tickets-admin.*', 'can' => 'tickets.handle'],
                    ['label' => 'Inhalte', 'group' => true, 'route' => 'articles.admin.index', 'children' => [
                        ['description' => 'Artikel für Startseite und Artikelliste.', 'label' => 'Artikel', 'route' => 'articles.admin.index', 'match' => 'articles.admin.*', 'can' => 'articles.manage'],
                        ['description' => 'Rechtstexte und andere Seiten.', 'label' => 'Seiten', 'route' => 'pages.admin.index', 'match' => 'pages.admin.*', 'can' => 'pages.manage'],
                        ['description' => 'Themen und Formulare des öffentlichen Kontaktbereichs.', 'label' => 'Kontaktformular', 'route' => 'contact-admin.topics.index', 'match' => 'contact-admin.*', 'can' => 'contact_forms.manage'],
                        ['description' => 'Fragen und Antworten für die FAQ-Seite des Portals.', 'label' => 'FAQ', 'route' => 'faq-admin.index', 'match' => 'faq-admin.*', 'can' => 'faq.manage'],
                        ['description' => 'Wichtige Links je Rolle für das Profilmenü.', 'label' => 'Schnellzugriffe', 'route' => 'role-links.edit', 'match' => 'role-links.*', 'can' => 'role_links.manage'],
                        ['description' => 'E-Mails an Gruppen wie Teamende, Schulen oder Koordination.', 'label' => 'Rundschreiben', 'route' => 'mailings.office.index', 'match' => 'mailings.office.*', 'can' => 'mailings.send'],
                        ['description' => 'Gespeicherte Empfängerkreise mit Filtern für Rundschreiben.', 'label' => 'Verteiler', 'route' => 'mailings.segments.index', 'match' => 'mailings.segments.*', 'can' => 'mail_segments.manage'],
                        ['description' => 'Vorlagen für Rundschreiben und für Mitteilungen an Teamende.', 'label' => 'Mailvorlagen', 'route' => 'mailings.templates.index', 'match' => 'mailings.templates.*', 'can' => 'mail_templates.manage'],
                        ['description' => 'Fehlerquote, Sperren und Abmeldungen der Rundschreiben, Prüfung der Absenderdomain.', 'label' => 'Zustellqualität', 'route' => 'mailings.quality.index', 'match' => 'mailings.quality.*', 'can' => 'mail_templates.manage'],
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
                    ['label' => 'System', 'group' => true, 'route' => 'system.index', 'children' => [
                        ['description' => 'Zustand des Portals: Prüfungen, Versionen, Planer.', 'label' => 'Zustand', 'route' => 'system.index', 'match' => 'system.index', 'can' => 'system.manage'],
                        ['description' => 'Aufgaben ausführen: Cache leeren, Migrationen, geplante Aufgaben.', 'label' => 'Aufgaben', 'route' => 'system.tasks', 'match' => 'system.tasks*', 'can' => 'system.manage'],
                        ['description' => 'Fehler und Warnungen der Anwendung aus der Protokolldatei.', 'label' => 'Fehlerprotokoll', 'route' => 'system.logs', 'match' => 'system.logs', 'can' => 'system.manage'],
                        ['description' => 'Fehlgeschlagene Aufträge erneut versuchen oder verwerfen.', 'label' => 'Warteschlange', 'route' => 'system.queue', 'match' => 'system.queue*', 'can' => 'system.manage'],
                        ['description' => 'Fristen, E-Mail und Betrieb zum Nachlesen, Testmail senden.', 'label' => 'Einstellungen', 'route' => 'system.settings', 'match' => 'system.settings*', 'can' => 'system.manage'],
                    ]],
                    ['description' => 'Nextcloud, Moodle und andere Systeme, die sich über das Portal anmelden.', 'label' => 'Anbindungen', 'route' => 'admin.oauth.index', 'match' => 'admin.oauth.*', 'can' => 'oauth.manage'],
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
            'membership.*' => 'users', 'onboarding.*' => 'clipboard-list', 'team-onboarding.*' => 'clipboard-list', 'mailings.*' => 'mail', 'board.meetings.*' => 'calendar', 'board.resolutions.*' => 'landmark',
            'board.reports*' => 'file', 'member.membership' => 'users', 'forms.*' => 'file', 'forms-office.*' => 'file', 'seminars.*' => 'graduation-cap', 'seminar-*' => 'file', 'partners.*' => 'school',
            'feedback.*' => 'star', 'contracts.*' => 'file', 'letters.*' => 'pencil', 'events.*' => 'ticket', 'team.*' => 'user', 'school.*' => 'school', 'admin.users.*' => 'users', 'access-requests.*' => 'key-round',
            'admin.people.*' => 'user', 'contacts.*' => 'mail', 'articles.*' => 'book-open', 'pages.*' => 'file', 'contact-admin.*' => 'mail', 'faq-admin.*' => 'circle-help', 'role-links.*' => 'link', 'support.*' => 'ticket', 'tickets-admin.*' => 'ticket', 'system.*' => 'settings',
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

    /**
     * Zu welchem Bereich gehört die aktuelle Seite? Wie areaForRoute, aber auch für die Seiten des Aufgabensystems, die zum Bereich ihres Projekts gehören
     * (Projekt, Aufgabe, Vorlagen) oder des Menüpunkts „Aufgaben“ (tasks.area). Kann die Person diesen Bereich nicht betreten (z. B. Mitglied in einem
     * fremden Projekt), bleibt sie in einem ihrer Bereiche mit Aufgaben, sonst im Konto.
     */
    public static function areaForRequest(?Request $request): ?PortalAreas
    {
        $route = $request?->route();
        $name = $route?->getName();

        if ($name === null) {
            return null;
        }

        if (! self::isProjectBoundRoute($name)) {
            return self::areaForRoute($name);
        }

        if ($name === 'tasks.area') {
            $area = PortalAreas::tryFrom((string) $route->parameter('area'));
        } else {
            $model = $route->parameter('project') ?? $route->parameter('task');
            $model = $model instanceof Task ? $model->project : $model;
            $area = $model instanceof TaskProject ? $model->portalArea() : null;
        }

        $user = $request->user();

        if ($area !== null && ($user === null || self::canEnter($area, $user))) {
            return $area;
        }

        // Ohne Zutritt: ein eigener Bereich mit Aufgaben, sonst das Konto
        foreach ($user === null ? [] : self::visibleAreas($user) as $own) {
            if (in_array($own, self::taskAreas(), true)) {
                return $own;
            }
        }

        return PortalAreas::Konto;
    }

    /** Gehört die Aufgaben-Route zum Bereich eines Projekts (nicht zu einem festen Bereich wie „Meine Aufgaben“ im Konto)? */
    public static function isProjectBoundRoute(string $routeName): bool
    {
        return Str::is('tasks.*', $routeName) && ! in_array($routeName, self::FIXED_TASK_ROUTES, true);
    }

    /** Zu welchem Bereich gehört eine Seite (Routenname)? Null für Seiten ohne Bereich (Anmeldung, Fehlerseiten) und für Aufgaben-Seiten, die zum Bereich ihres Projekts gehören (areaForRequest). */
    public static function areaForRoute(?string $routeName): ?PortalAreas
    {
        if ($routeName === null || self::isProjectBoundRoute($routeName)) {
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

<?php

namespace App\Modules\AccessGovernance\Enums;

/** Berechtigungen der Verwaltung. Welche Rolle welche hat, steht in Support\Capabilities. */
enum Capability: string
{
    /** Nutzerliste und Nutzerdetails ansehen. */
    case UsersView = 'users.view';

    /** Nutzer anlegen, einladen, sperren und entsperren, Zwei-Faktor zurücksetzen. */
    case UsersManage = 'users.manage';

    /** Rollen vergeben und beenden (die Rolle Administration nur durch Administration). */
    case RolesAssign = 'roles.assign';

    /** Personenliste und Personendetails ansehen. */
    case PeopleView = 'people.view';

    /** Personen anlegen, bearbeiten und (ohne Konto) löschen. */
    case PeopleManage = 'people.manage';

    /** Mitgliedschaften ansehen (Bereich Vorstand). */
    case MembershipsView = 'memberships.view';

    /** Mitgliedschaften anlegen, aufnehmen, ablehnen, Austritt eintragen. */
    case MembershipsManage = 'memberships.manage';

    /** Schulen als Partner ansehen und exportieren (Bereich Koordination). */
    case PartnersView = 'partners.view';

    /** Schulen anlegen, ändern, löschen und per CSV importieren. */
    case PartnersManage = 'partners.manage';

    /** Importierte Mailingliste (Teamkontakte) und persönliche Zugänge verwalten (Geschäftsstelle). */
    case ContactsManage = 'contacts.manage';

    /** Einarbeitung der Teamenden ansehen (Bereich Koordination). */
    case TeamView = 'team.view';

    /** Schritte der Einarbeitung eintragen und die Dokumente der Teamenden hochladen. */
    case TeamManage = 'team.manage';

    /** Die eigene Einarbeitung als Teamende sehen und Erklärungen bestätigen (Bereich Teamende). */
    case TeamOnboardingOwn = 'team.onboarding';

    /** Zugangsanträge ansehen (Bereich Geschäftsstelle). */
    case AccessRequestsView = 'access_requests.view';

    /** Zugangsanträge prüfen, annehmen und ablehnen. */
    case AccessRequestsManage = 'access_requests.manage';

    /** Artikel für die öffentlichen Seiten schreiben und veröffentlichen. */
    case ArticlesManage = 'articles.manage';

    /** Rechtstext-Seiten (Impressum, Datenschutz, …) bearbeiten. */
    case PagesManage = 'pages.manage';

    /** Mitgliederbereich: eigene Mitgliedschaft, Satzung und Willkommensbrief. */
    case MemberArea = 'member.area';

    /** Bereich Bildungsreferenten: Übersicht mit den eigenen Aufgaben. */
    case EducationView = 'education.view';

    /** Bereich Schulen: Startseite der eigenen Schule. */
    case SchoolArea = 'school.area';

    /** Jahresberichte mit Kennzahlen ansehen und exportieren (Vorstand). */
    case ReportsView = 'reports.view';

    /** Design-Bibliothek ansehen (Entwicklungswerkzeug). */
    case DesignView = 'design.view';

    /** Schreiben an Gruppen (Teamende, Schulen, Koordination, Vorstand, Geschäftsstelle …) verfassen und senden (Geschäftsstelle und Vorstand). */
    case MailingsSend = 'mailings.send';

    /** Zusätzlich die Gruppe Mitglieder anschreiben, z. B. Einladung zur Versammlung (Vorstand). */
    case MailingsMembers = 'mailings.members';

    /** Rundschreiben an Teamende, Schulen, Koordination und Bildungsreferenten (Koordination, ohne Mitglieder und Vorstand). */
    case MailingsSendLimited = 'mailings.send_limited';

    /** Schreiben an Gruppen freigeben (Vorstand): Schreiben der Geschäftsstelle an viele Personen brauchen die Freigabe. */
    case MailingsApprove = 'mailings.approve';

    /** Mailvorlagen für Mitteilungen bearbeiten (Geschäftsstelle). */
    case MailTemplatesManage = 'mail_templates.manage';

    /** Verteiler (gespeicherte Empfängerkreise) anlegen und ändern (Geschäftsstelle, Vorstand). */
    case MailSegmentsManage = 'mail_segments.manage';

    /** Vorstandssitzungen mit Tagesordnung, Anwesenheit, Protokoll und Kalender-Abo verwalten (nur Vorstand). */
    case BoardMeetings = 'board.meetings';

    /** Das Aufgabensystem nutzen: Projekte ansehen, in denen man Mitglied ist, Aufgaben anlegen, bearbeiten und verschieben. */
    case TasksUse = 'tasks.use';

    /** Im Aufgabensystem alle Projekte sehen und verwalten, Projekte anlegen und löschen (Administration, Vorstand). */
    case TasksAdmin = 'tasks.admin';

    /** Themen, FAQ-Seiten und Formulare des öffentlichen Kontaktbereichs pflegen (Administration, Geschäftsstelle). */
    case ContactFormsManage = 'contact_forms.manage';

    /** Die große FAQ-Seite pflegen: Kategorien und Fragen (Administration, Geschäftsstelle). */
    case FaqManage = 'faq.manage';

    /** Wichtige Links je Rolle für das Profilmenü pflegen (Administration, Geschäftsstelle). */
    case RoleLinksManage = 'role_links.manage';

    /** Koordinationen anlegen, ändern und deaktivieren (Administration, Geschäftsstelle, Vorstand). */
    case CoordinationsManage = 'coordinations.manage';

    /** Bereiche und Seiten des Kopfmenüs ein- und ausblenden (Administration). */
    case NavigationManage = 'navigation.manage';

    /** Tickets aller Personen sehen, zuweisen und bearbeiten (Administration, Geschäftsstelle). */
    case TicketsHandle = 'tickets.handle';

    /** Systemsteuerung: Zustand ansehen und Aufgaben ausführen (nur Administration). */
    case SystemManage = 'system.manage';

    /** Protokoll ansehen und exportieren. */
    case AuditView = 'audit.view';

    /** Anbindungen (OAuth-Clients) für Nextcloud, Moodle und andere Systeme verwalten, Schlüssel erneuern (nur Administration). */
    case OauthManage = 'oauth.manage';

    /** Anträge und Formulare der Geschäftsstelle einreichen (Mitarbeitende, Geschäftsführung, Kassenführung). */
    case FormsSubmit = 'forms.submit';

    /** Anträge prüfen, Ausnahmen, Export und Kostenstellen (Kassenführung). */
    case FormsManage = 'forms.manage';

    /** Anträge ab dem Schwellenwert freigeben (Geschäftsführung; nie den eigenen Antrag). */
    case FormsApprove = 'forms.approve';

    /** Den Bereich Mitarbeitende sehen. */
    case EmployeeArea = 'employee.area';

    /** Anträge in der Geschäftsstelle ansehen (Geschäftsstelle, Vorstand, Geschäftsführung, Kassenführung). */
    case FormsView = 'forms.view';

    /** Anträge als Teamende stellen (Bereich Teamende). */
    case FormsSubmitTeam = 'forms.submit_team';

    /** Anträge als Vereinsmitglied stellen (Bereich Mitglieder). */
    case FormsSubmitMember = 'forms.submit_member';
}

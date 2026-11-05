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

    /** Seminare ansehen (Bereich Koordination). */
    case SeminarsView = 'seminars.view';

    /** Seminare anlegen, ändern, Status wechseln, Termine pflegen und Kostenvoranschläge erstellen. */
    case SeminarsManage = 'seminars.manage';

    /** Dokumente zu Seminaren ablegen, herunterladen, löschen und die Seminarmappe laden. */
    case SeminarsDocuments = 'seminars.documents';

    /** Einen abgeschlossenen Seminarabschluss wieder öffnen und geschützte Dokumente nach dem Abschluss löschen (Vorstand). */
    case SeminarsReopen = 'seminars.reopen';

    /** Feedbackformulare bearbeiten und Feedbacklinks zu Seminaren anlegen. */
    case FeedbackManage = 'feedback.manage';

    /** Rückmeldungen zu Seminaren lesen und auswerten (nur Koordination). */
    case FeedbackView = 'feedback.view';

    /** Preisliste der Seminare ändern (neue Fassung anlegen). */
    case SeminarPricesManage = 'seminars.prices';

    /** Einarbeitung der Teamenden ansehen (Bereich Koordination). */
    case TeamView = 'team.view';

    /** Schritte der Einarbeitung eintragen und die Dokumente der Teamenden hochladen. */
    case TeamManage = 'team.manage';

    /** Die eigene Einarbeitung als Teamende sehen und Erklärungen bestätigen (Bereich Teamende). */
    case TeamOnboardingOwn = 'team.onboarding';

    /** Offene Seminare sehen, sich bewerben und den eigenen Kalender abrufen (Teamende). */
    case SeminarsApply = 'seminars.apply';

    /** Zugangsanträge ansehen (Bereich Geschäftsstelle). */
    case AccessRequestsView = 'access_requests.view';

    /** Zugangsanträge prüfen, annehmen und ablehnen. */
    case AccessRequestsManage = 'access_requests.manage';

    /** Artikel für die öffentlichen Seiten schreiben und veröffentlichen. */
    case ArticlesManage = 'articles.manage';

    /** Rechtstext-Seiten (Impressum, Datenschutz, …) bearbeiten. */
    case PagesManage = 'pages.manage';

    /** Großveranstaltungen mit Anmeldungen ansehen (Bereich Koordination). */
    case EventsView = 'events.view';

    /** Großveranstaltungen anlegen, Anmeldungen importieren, Anwesenheit führen. */
    case EventsManage = 'events.manage';

    /** Mitgliederbereich: eigene Mitgliedschaft, Satzung und Willkommensbrief. */
    case MemberArea = 'member.area';

    /** Bereich Bildungsreferenten: Überblick über Seminare und Veranstaltungen (nur lesen). */
    case EducationView = 'education.view';

    /** Bereich Schulen: Seminare der eigenen Schule, Kostenvoranschläge, Anfragen und Bestätigungen. */
    case SchoolArea = 'school.area';

    /** Jahresberichte mit Kennzahlen ansehen und exportieren (Vorstand). */
    case ReportsView = 'reports.view';

    /** Design-Bibliothek ansehen (Entwicklungswerkzeug). */
    case DesignView = 'design.view';

    /** Schreiben an Gruppen (Teamende, Schulen, Koordination, Vorstand, Geschäftsstelle …) verfassen und senden (Geschäftsstelle und Vorstand). */
    case MailingsSend = 'mailings.send';

    /** Zusätzlich die Gruppe Mitglieder anschreiben, z. B. Einladung zur Versammlung (Vorstand). */
    case MailingsMembers = 'mailings.members';

    /** Mitteilungen an Teamende zu Seminaren senden (Koordination). */
    case MailingsTeam = 'mailings.team';

    /** Schreiben an Gruppen freigeben (Vorstand): Schreiben der Geschäftsstelle an viele Personen brauchen die Freigabe. */
    case MailingsApprove = 'mailings.approve';

    /** Mailvorlagen für Mitteilungen bearbeiten (Geschäftsstelle). */
    case MailTemplatesManage = 'mail_templates.manage';

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
}

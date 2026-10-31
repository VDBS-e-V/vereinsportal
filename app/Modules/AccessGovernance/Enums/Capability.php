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

    /** Seminare ansehen (Bereich Koordination). */
    case SeminarsView = 'seminars.view';

    /** Seminare anlegen, ändern, Status wechseln, Termine pflegen und Kostenvoranschläge erstellen. */
    case SeminarsManage = 'seminars.manage';

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

    /** Design-Bibliothek ansehen (Entwicklungswerkzeug). */
    case DesignView = 'design.view';

    /** Protokoll ansehen und exportieren. */
    case AuditView = 'audit.view';
}

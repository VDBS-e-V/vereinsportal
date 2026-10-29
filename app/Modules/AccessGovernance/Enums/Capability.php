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

    /** Design-Bibliothek ansehen (Entwicklungswerkzeug). */
    case DesignView = 'design.view';

    /** Protokoll ansehen und exportieren. */
    case AuditView = 'audit.view';
}

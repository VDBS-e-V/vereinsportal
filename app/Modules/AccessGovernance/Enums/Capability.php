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
}

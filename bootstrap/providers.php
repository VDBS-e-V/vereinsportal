<?php

use App\Modules\Access\AccessServiceProvider;
use App\Modules\AccessGovernance\AccessGovernanceServiceProvider;
use App\Modules\Audit\AuditServiceProvider;
use App\Modules\DesignLibrary\DesignLibraryServiceProvider;
use App\Modules\People\PeopleServiceProvider;
use App\Modules\Sso\SsoServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\VoltServiceProvider;

/*
 * IdentityServiceProvider, AuditServiceProvider und
 * CommunicationServiceProvider sind mit ihren Modulen entfernt worden
 * und kommen zurueck, sobald Access/Compliance-Log/Messaging neu
 * aufgebaut sind.
 */
return [
    AppServiceProvider::class,
    VoltServiceProvider::class,
    AccessServiceProvider::class,
    AccessGovernanceServiceProvider::class,
    AuditServiceProvider::class,
    PeopleServiceProvider::class,
    SsoServiceProvider::class,
    DesignLibraryServiceProvider::class,
];

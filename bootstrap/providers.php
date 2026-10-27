<?php

use App\Modules\Access\AccessServiceProvider;
use App\Modules\DesignLibrary\DesignLibraryServiceProvider;
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
    SsoServiceProvider::class,
    DesignLibraryServiceProvider::class,
];

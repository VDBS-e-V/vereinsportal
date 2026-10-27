<?php

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
];

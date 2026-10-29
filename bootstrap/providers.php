<?php

use App\Modules\Access\AccessServiceProvider;
use App\Modules\AccessGovernance\AccessGovernanceServiceProvider;
use App\Modules\Audit\AuditServiceProvider;
use App\Modules\DesignLibrary\DesignLibraryServiceProvider;
use App\Modules\Membership\MembershipServiceProvider;
use App\Modules\Partners\PartnersServiceProvider;
use App\Modules\People\PeopleServiceProvider;
use App\Modules\Seminars\SeminarsServiceProvider;
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
    PartnersServiceProvider::class,
    SeminarsServiceProvider::class,
    PeopleServiceProvider::class,
    MembershipServiceProvider::class,
    SsoServiceProvider::class,
    DesignLibraryServiceProvider::class,
];

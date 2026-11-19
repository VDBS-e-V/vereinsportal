<?php

use App\Modules\Access\AccessServiceProvider;
use App\Modules\AccessGovernance\AccessGovernanceServiceProvider;
use App\Modules\AccessRequests\AccessRequestsServiceProvider;
use App\Modules\Audit\AuditServiceProvider;
use App\Modules\DesignLibrary\DesignLibraryServiceProvider;
use App\Modules\MailBuilder\MailBuilderServiceProvider;
use App\Modules\NavigationControl\NavigationControlServiceProvider;
use App\Modules\Pages\PagesServiceProvider;
use App\Modules\People\PeopleServiceProvider;
use App\Modules\QuickLinks\QuickLinksServiceProvider;
use App\Modules\Setup\SetupServiceProvider;
use App\Modules\Sso\SsoServiceProvider;
use App\Modules\System\SystemServiceProvider;
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
    QuickLinksServiceProvider::class,
    NavigationControlServiceProvider::class,
    PeopleServiceProvider::class,
    AccessRequestsServiceProvider::class,
    SystemServiceProvider::class,
    SetupServiceProvider::class,
    MailBuilderServiceProvider::class,
    PagesServiceProvider::class,
    SsoServiceProvider::class,
    DesignLibraryServiceProvider::class,
];

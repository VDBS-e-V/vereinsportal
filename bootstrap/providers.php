<?php

use App\Modules\Audit\AuditServiceProvider;
use App\Modules\Communication\CommunicationServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    AppServiceProvider::class,
    VoltServiceProvider::class,

    IdentityServiceProvider::class,
    AuditServiceProvider::class,
    CommunicationServiceProvider::class,
];

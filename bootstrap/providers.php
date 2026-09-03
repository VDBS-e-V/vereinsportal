<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,

    App\Modules\Identity\IdentityServiceProvider::class,
    App\Modules\Audit\AuditServiceProvider::class,
    App\Modules\Communication\CommunicationServiceProvider::class,
];
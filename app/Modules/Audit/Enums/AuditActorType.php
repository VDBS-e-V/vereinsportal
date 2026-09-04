<?php

namespace App\Modules\Audit\Enums;

enum AuditActorType: string
{
    case User = 'user';
    case System = 'system';
    case Console = 'console';
}

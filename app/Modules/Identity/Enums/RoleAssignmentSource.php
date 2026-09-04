<?php

namespace App\Modules\Identity\Enums;

enum RoleAssignmentSource: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';
    case Console = 'console';
}

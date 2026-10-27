<?php

namespace App\Modules\AccessGovernance\Enums;

enum RoleAssignmentSource: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';
    case Console = 'console';
}

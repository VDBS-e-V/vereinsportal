<?php

namespace App\Modules\Identity\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case PendingVerification = 'pending_verification';
    case Disabled = 'disabled';
    case PendingDeletion = 'pending_deletion';
    case Anonymized = 'anonymized';
}
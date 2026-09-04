<?php

namespace App\Modules\Identity\Enums;

enum RegistrationRequestStatus: string
{
    case PendingVerification = 'pending_verification';
}

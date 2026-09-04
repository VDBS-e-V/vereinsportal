<?php

namespace App\Modules\Identity\Enums;

enum AccountDeletionRequestStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case PendingDeletion = 'pending_deletion';
    case Withdrawn = 'withdrawn';
    case Stopped = 'stopped';
    case Completed = 'completed';
}

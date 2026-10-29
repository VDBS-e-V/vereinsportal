<?php

namespace App\Modules\Membership\Enums;

enum MembershipEventType: string
{
    case Requested = 'requested';
    case Admitted = 'admitted';
    case AdmittedDirectly = 'admitted_directly';
    case Rejected = 'rejected';
    case TerminationSet = 'termination_set';
    case TerminationWithdrawn = 'termination_withdrawn';
    case Ended = 'ended';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Antrag erfasst',
            self::Admitted => 'Aufgenommen',
            self::AdmittedDirectly => 'Direkt aufgenommen',
            self::Rejected => 'Antrag abgelehnt',
            self::TerminationSet => 'Austritt eingetragen',
            self::TerminationWithdrawn => 'Austritt zurückgenommen',
            self::Ended => 'Mitgliedschaft beendet',
            self::Note => 'Notiz',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Admitted, self::AdmittedDirectly => 'success',
            self::Rejected => 'error',
            self::TerminationSet, self::Ended => 'warning',
            default => 'neutral',
        };
    }
}

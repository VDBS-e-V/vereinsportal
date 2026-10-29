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
    case RegistrationLinkSent = 'registration_link_sent';
    case Registered = 'registered';
    case DocumentAdded = 'document_added';
    case DocumentRemoved = 'document_removed';
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
            self::RegistrationLinkSent => 'Registrierungslink gesendet',
            self::Registered => 'Online-Konto registriert',
            self::DocumentAdded => 'Dokument hochgeladen',
            self::DocumentRemoved => 'Dokument entfernt',
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

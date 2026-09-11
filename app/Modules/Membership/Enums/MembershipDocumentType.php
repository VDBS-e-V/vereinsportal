<?php

namespace App\Modules\Membership\Enums;

enum MembershipDocumentType: string
{
    case ApplicationForm = 'application_form';
    case ConsentEvidence = 'consent_evidence';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationForm => 'Beitrittserklärung',
            self::ConsentEvidence => 'Zustimmungsnachweis',
            self::Other => 'Sonstiger Nachweis',
        };
    }
}

<?php

namespace App\Modules\Quotes\Enums;

/** Stand einer Version des Kostenvoranschlags. */
enum QuoteStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Approved => 'Intern freigegeben',
            self::Sent => 'Gesendet',
            self::Accepted => 'Angenommen',
            self::Declined => 'Abgelehnt',
            self::Superseded => 'Ersetzt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Accepted => 'success',
            self::Declined => 'error',
            self::Draft, self::Approved, self::Sent => 'warning',
            self::Superseded => 'neutral',
        };
    }

    /** Solange der KVA nicht gesendet ist, lässt er sich bearbeiten. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Approved], true);
    }
}

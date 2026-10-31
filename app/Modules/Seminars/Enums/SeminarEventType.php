<?php

namespace App\Modules\Seminars\Enums;

enum SeminarEventType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case SessionAdded = 'session_added';
    case SessionRemoved = 'session_removed';
    case QuoteCreated = 'quote_created';
    case QuoteUpdated = 'quote_updated';
    case Note = 'note';
    case TeamNeedChanged = 'team_need_changed';
    case TeamApplied = 'team_applied';
    case TeamWithdrawn = 'team_withdrawn';
    case TeamConfirmed = 'team_confirmed';
    case TeamDeclined = 'team_declined';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Seminar angelegt',
            self::Updated => 'Angaben geändert',
            self::StatusChanged => 'Status geändert',
            self::SessionAdded => 'Termin hinzugefügt',
            self::SessionRemoved => 'Termin entfernt',
            self::QuoteCreated => 'Kostenvoranschlag erstellt',
            self::QuoteUpdated => 'Kostenvoranschlag geändert',
            self::Note => 'Notiz',
            self::TeamNeedChanged => 'Bedarf an Teamenden geändert',
            self::TeamApplied => 'Bewerbung eingegangen',
            self::TeamWithdrawn => 'Bewerbung zurückgezogen',
            self::TeamConfirmed => 'Teamende:r bestätigt',
            self::TeamDeclined => 'Bewerbung abgelehnt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::StatusChanged, self::QuoteCreated, self::QuoteUpdated, self::TeamConfirmed => 'success',
            self::SessionRemoved, self::TeamWithdrawn, self::TeamDeclined => 'warning',
            default => 'neutral',
        };
    }
}

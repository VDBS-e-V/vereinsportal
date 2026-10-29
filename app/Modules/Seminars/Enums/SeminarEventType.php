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
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::StatusChanged, self::QuoteCreated, self::QuoteUpdated => 'success',
            self::SessionRemoved => 'warning',
            default => 'neutral',
        };
    }
}

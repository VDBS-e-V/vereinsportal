<?php

namespace App\Modules\AccessRequests\Enums;

enum AccessRequestStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Neu',
            self::InReview => 'In Prüfung',
            self::Approved => 'Angenommen',
            self::Rejected => 'Abgelehnt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Approved => 'success',
            self::Rejected => 'error',
            default => 'neutral',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::New || $this === self::InReview;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

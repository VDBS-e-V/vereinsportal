<?php

namespace App\Modules\Seminars\Enums;

/** Leistungsart eines Seminars. Bestimmt die Voreinstellungen des Kostenrechners (siehe QuoteCalculator::defaults). */
enum SeminarType: string
{
    case SvDays = 'sv_days';
    case Coaching = 'coaching';
    case Election = 'election';

    public function label(): string
    {
        return match ($this) {
            self::SvDays => 'SV-Tage',
            self::Coaching => 'SV-Coaching',
            self::Election => 'SV-Wahl',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

<?php

namespace App\Modules\Seminars\Enums;

/** Leistungsart eines Seminars. Bestimmt die Voreinstellungen des Kostenrechners (siehe QuoteCalculator::defaults). */
enum SeminarType: string
{
    case SvDays = 'sv_days';
    case Coaching = 'coaching';
    case Election = 'election';
    case GsvSupport = 'gsv_support';

    public function label(): string
    {
        return match ($this) {
            self::SvDays => 'SV-Tage',
            self::Coaching => 'SV-Coaching',
            self::Election => 'SV-Wahl',
            self::GsvSupport => 'Begleitung GSV-Sitzungen',
        };
    }

    /** Was die Stückzahl des Produkts zählt (Tage, Treffen, Sitzungen). */
    public function unit(): string
    {
        return match ($this) {
            self::SvDays => 'Tage',
            self::Coaching, self::Election => 'Treffen',
            self::GsvSupport => 'Sitzungen',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

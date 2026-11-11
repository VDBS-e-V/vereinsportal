<?php

namespace App\Modules\Seminars\Enums;

/** Freiwillige Selbstangabe im Teamenden-Profil, nur für die Koordination sichtbar und nur für ausgewogene Teams. Keine Angabe = nicht gesetzt. */
enum TeamGender: string
{
    case Female = 'female';
    case Male = 'male';
    case Diverse = 'diverse';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'weiblich',
            self::Male => 'männlich',
            self::Diverse => 'divers',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

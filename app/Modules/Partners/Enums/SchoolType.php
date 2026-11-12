<?php

namespace App\Modules\Partners\Enums;

/** Schularten für Schulen als Partner (Feld school_type in partners.attributes). */
enum SchoolType: string
{
    case Primary = 'grundschule';
    case SecondaryGeneral = 'oberschule';
    case Comprehensive = 'gesamtschule';
    case Gymnasium = 'gymnasium';
    case Vocational = 'berufsschule';
    case Special = 'foerderschule';
    case Other = 'sonstige';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Grundschule',
            self::SecondaryGeneral => 'Oberschule / Sekundarschule',
            self::Comprehensive => 'Gesamtschule / Gemeinschaftsschule',
            self::Gymnasium => 'Gymnasium',
            self::Vocational => 'Berufsschule / Berufskolleg',
            self::Special => 'Förderschule',
            self::Other => 'Sonstige',
        };
    }

    /** @return array<string, string> Schlüssel => Bezeichnung */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }

    /** Erkennt Schlüssel oder Bezeichnung (ohne Beachtung der Groß-/Kleinschreibung), sonst null. */
    public static function fromInput(string $input): ?self
    {
        $needle = mb_strtolower(trim($input));

        foreach (self::cases() as $case) {
            if ($needle === $case->value || $needle === mb_strtolower($case->label())) {
                return $case;
            }
        }

        return null;
    }
}

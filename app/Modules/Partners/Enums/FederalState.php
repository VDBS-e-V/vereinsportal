<?php

namespace App\Modules\Partners\Enums;

/** Bundesländer (Feld federal_state in partners.attributes), Schlüssel nach ISO 3166-2:DE. */
enum FederalState: string
{
    case BW = 'BW';
    case BY = 'BY';
    case BE = 'BE';
    case BB = 'BB';
    case HB = 'HB';
    case HH = 'HH';
    case HE = 'HE';
    case MV = 'MV';
    case NI = 'NI';
    case NW = 'NW';
    case RP = 'RP';
    case SL = 'SL';
    case SN = 'SN';
    case ST = 'ST';
    case SH = 'SH';
    case TH = 'TH';

    public function label(): string
    {
        return match ($this) {
            self::BW => 'Baden-Württemberg',
            self::BY => 'Bayern',
            self::BE => 'Berlin',
            self::BB => 'Brandenburg',
            self::HB => 'Bremen',
            self::HH => 'Hamburg',
            self::HE => 'Hessen',
            self::MV => 'Mecklenburg-Vorpommern',
            self::NI => 'Niedersachsen',
            self::NW => 'Nordrhein-Westfalen',
            self::RP => 'Rheinland-Pfalz',
            self::SL => 'Saarland',
            self::SN => 'Sachsen',
            self::ST => 'Sachsen-Anhalt',
            self::SH => 'Schleswig-Holstein',
            self::TH => 'Thüringen',
        };
    }

    /** @return array<string, string> Schlüssel => Bezeichnung */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }

    /** Erkennt Kürzel oder Name (ohne Beachtung der Groß-/Kleinschreibung), sonst null. */
    public static function fromInput(string $input): ?self
    {
        $needle = mb_strtolower(trim($input));

        foreach (self::cases() as $case) {
            if ($needle === mb_strtolower($case->value) || $needle === mb_strtolower($case->label())) {
                return $case;
            }
        }

        return null;
    }
}

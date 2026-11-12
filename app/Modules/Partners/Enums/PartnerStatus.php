<?php

namespace App\Modules\Partners\Enums;

/** Stand der Partnerschaft: von „soll angesprochen werden“ bis „vergangen“. */
enum PartnerStatus: string
{
    case ToApproach = 'to_approach';
    case Planned = 'planned';
    case Discussion = 'discussion';
    case Active = 'active';
    case Paused = 'paused';
    case Past = 'past';

    public function label(): string
    {
        return match ($this) {
            self::ToApproach => 'Soll angesprochen werden',
            self::Planned => 'In Planung',
            self::Discussion => 'In Vorbesprechung',
            self::Active => 'Aktiv',
            self::Paused => 'Pausiert',
            self::Past => 'Vergangen',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Paused => 'warning',
            default => 'neutral',
        };
    }

    /** Noch nicht aktiv: die Partnerschaft wird angebahnt. */
    public function isPipeline(): bool
    {
        return in_array($this, [self::ToApproach, self::Planned, self::Discussion], true);
    }

    /** @return list<string> Werte der Anbahnung */
    public static function pipelineValues(): array
    {
        return array_values(array_map(fn (self $case): string => $case->value, array_filter(self::cases(), fn (self $case): bool => $case->isPipeline())));
    }

    /** Status aus Wert oder Bezeichnung (CSV-Import), z. B. „aktiv“, „In Planung“, „pausiert“; leer = aktiv; alte Werte „inaktiv“ = pausiert. */
    public static function fromInput(string $input): ?self
    {
        $key = mb_strtolower(trim($input));

        if ($key === '') {
            return self::Active;
        }

        foreach (self::cases() as $case) {
            if ($key === $case->value || $key === mb_strtolower($case->label())) {
                return $case;
            }
        }

        return match ($key) {
            'inaktiv', 'inactive' => self::Paused,
            'angesprochen werden', 'ansprechen' => self::ToApproach,
            'planung' => self::Planned,
            'vorbesprechung' => self::Discussion,
            default => null,
        };
    }
}

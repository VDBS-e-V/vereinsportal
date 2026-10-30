<?php

namespace App\Modules\AccessRequests\Enums;

use App\Modules\AccessGovernance\Enums\RoleKey;

/** Verbindung zum Verein im Zugangsantrag. Vereinsmitglieder stellen keinen Zugangsantrag, sie wenden sich an den Vorstand. */
enum AccessConnection: string
{
    case Team = 'team';
    case Student = 'student';
    case Teacher = 'teacher';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Team => 'Teamer:in',
            self::Student => 'Schüler:in',
            self::Teacher => 'Lehrkraft',
            self::Partner => 'Partner:in',
        };
    }

    /** Rolle, die die Geschäftsstelle bei der Annahme vorschlägt (nur Teamende haben bisher eine Rolle). */
    public function suggestedRole(): ?RoleKey
    {
        return $this === self::Team ? RoleKey::Team : null;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

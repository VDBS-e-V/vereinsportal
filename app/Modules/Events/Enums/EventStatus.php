<?php

namespace App\Modules\Events\Enums;

/** Stand einer Großveranstaltung: Entwurf (nur intern), Offen (Anmeldung möglich), Geschlossen (keine Anmeldung mehr), Durchgeführt. */
enum EventStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Open => 'Anmeldung offen',
            self::Closed => 'Anmeldung geschlossen',
            self::Done => 'Durchgeführt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Closed => 'warning',
            default => 'neutral',
        };
    }

    /** Öffentlich sichtbar (Liste und Detailseite). */
    public function isPublic(): bool
    {
        return $this !== self::Draft;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

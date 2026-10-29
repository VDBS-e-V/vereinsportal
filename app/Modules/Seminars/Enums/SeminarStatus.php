<?php

namespace App\Modules\Seminars\Enums;

/** Ablauf eines Seminars: Anfrage → In Planung → Terminiert → Bestätigt → Durchgeführt → Abgeschlossen, jederzeit Abgesagt. */
enum SeminarStatus: string
{
    case Request = 'request';
    case Planning = 'planning';
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Request => 'Anfrage',
            self::Planning => 'In Planung',
            self::Scheduled => 'Terminiert',
            self::Confirmed => 'Bestätigt',
            self::Done => 'Durchgeführt',
            self::Completed => 'Abgeschlossen',
            self::Cancelled => 'Abgesagt',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Confirmed, self::Done, self::Completed => 'success',
            self::Cancelled => 'error',
            self::Scheduled => 'warning',
            default => 'neutral',
        };
    }

    /** @return list<self> Status, in die von hier gewechselt werden darf */
    public function next(): array
    {
        return match ($this) {
            self::Request => [self::Planning, self::Cancelled],
            self::Planning => [self::Request, self::Scheduled, self::Cancelled],
            self::Scheduled => [self::Planning, self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Scheduled, self::Done, self::Cancelled],
            self::Done => [self::Confirmed, self::Completed],
            self::Completed => [],
            self::Cancelled => [self::Request],
        };
    }

    public function canChangeTo(self $target): bool
    {
        return in_array($target, $this->next(), true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

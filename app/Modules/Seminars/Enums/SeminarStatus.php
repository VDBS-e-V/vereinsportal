<?php

namespace App\Modules\Seminars\Enums;

/** Status eines Seminars von der Anfrage der Schule bis zum Abschluss. Die erlaubten Übergänge stehen in SeminarWorkflow. */
enum SeminarStatus: string
{
    case Request = 'request';
    case InReview = 'in_review';
    case QuoteSent = 'quote_sent';
    case Released = 'released';
    case Planned = 'planned';
    case Tendered = 'tendered';
    case TeamSelected = 'team_selected';
    case TeamConfirmed = 'team_confirmed';
    case Prepared = 'prepared';
    case Held = 'held';
    case Reported = 'reported';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Request => 'Anfrage',
            self::InReview => 'In Prüfung',
            self::QuoteSent => 'KVA gesendet',
            self::Released => 'Freigegeben',
            self::Planned => 'Geplant',
            self::Tendered => 'Ausgeschrieben',
            self::TeamSelected => 'Team gewählt',
            self::TeamConfirmed => 'Team bestätigt',
            self::Prepared => 'Vorbereitet',
            self::Held => 'Durchgeführt',
            self::Reported => 'Berichtet',
            self::Completed => 'Abgeschlossen',
            self::Rejected => 'Abgelehnt',
            self::Cancelled => 'Abgesagt',
        };
    }

    /** Farbton für Badges (neutral, success, warning, error). */
    public function tone(): string
    {
        return match ($this) {
            self::Completed, self::Released, self::TeamConfirmed => 'success',
            self::Rejected, self::Cancelled => 'error',
            self::Request, self::InReview, self::QuoteSent => 'warning',
            default => 'neutral',
        };
    }

    /** Abgelehnt und Abgesagt sind Endzustände; sie lassen sich nur mit Begründung wieder öffnen. */
    public function isEnded(): bool
    {
        return in_array($this, [self::Rejected, self::Cancelled], true);
    }

    /** @return list<self> Status in der Reihenfolge des Ablaufs, ohne die Endzustände. */
    public static function flow(): array
    {
        return [self::Request, self::InReview, self::QuoteSent, self::Released, self::Planned, self::Tendered, self::TeamSelected, self::TeamConfirmed, self::Prepared, self::Held, self::Reported, self::Completed];
    }

    /** Stelle im Ablauf; die Endzustände zählen nicht. */
    public function position(): ?int
    {
        $index = array_search($this, self::flow(), true);

        return $index === false ? null : (int) $index;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }
}

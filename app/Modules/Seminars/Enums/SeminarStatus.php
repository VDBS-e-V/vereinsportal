<?php

namespace App\Modules\Seminars\Enums;

/**
 * Ablauf eines Seminars (docs/87_SEMINARABLAUF_UMSETZUNGSPLAN.md): Anfrage → In Prüfung → KVA gesendet → Freigegeben → Geplant → Ausgeschrieben →
 * Team gewählt → Team bestätigt → Vorbereitet → Durchgeführt → Berichtet → Abgeschlossen. Endzustände: Abgelehnt, Abgesagt. Die erlaubten Übergänge
 * stehen hier, die Bedingungen und Rechte im Dienst SeminarWorkflow.
 */
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

    public function tone(): string
    {
        return match ($this) {
            self::Released, self::TeamConfirmed, self::Prepared, self::Held, self::Reported, self::Completed => 'success',
            self::Rejected, self::Cancelled => 'error',
            self::Planned, self::Tendered, self::TeamSelected, self::QuoteSent => 'warning',
            default => 'neutral',
        };
    }

    /** @return list<self> Status, in die von hier gewechselt werden darf */
    public function next(): array
    {
        return match ($this) {
            self::Request => [self::InReview, self::Rejected, self::Cancelled],
            self::InReview => [self::Request, self::QuoteSent, self::Released, self::Rejected, self::Cancelled],
            self::QuoteSent => [self::InReview, self::Released, self::Rejected, self::Cancelled],
            self::Released => [self::InReview, self::Planned, self::Cancelled],
            self::Planned => [self::InReview, self::Released, self::Tendered, self::Cancelled],
            self::Tendered => [self::Planned, self::TeamSelected, self::Cancelled],
            self::TeamSelected => [self::Tendered, self::TeamConfirmed, self::Cancelled],
            self::TeamConfirmed => [self::Tendered, self::Prepared, self::Cancelled],
            self::Prepared => [self::TeamConfirmed, self::Held, self::Cancelled],
            // Durchgeführt → Abgeschlossen geht über die Abschluss-Checkliste, bis die Seminarleitung die Berichte hochlädt (Phase 5)
            self::Held => [self::Prepared, self::Reported, self::Completed],
            self::Reported => [self::Held, self::Completed],
            self::Completed => [],
            self::Rejected => [self::InReview, self::Request],
            self::Cancelled => [self::Request, self::InReview, self::Planned],
        };
    }

    public function canChangeTo(self $target): bool
    {
        return in_array($target, $this->next(), true);
    }

    /** Das Seminar ist beendet oder gescheitert. */
    public function isEnded(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled], true);
    }

    /** Status, in denen das Seminar gerade geplant oder vorbereitet wird (freigegeben bis vorbereitet). @return list<self> */
    public static function planning(): array
    {
        return [self::Released, self::Planned, self::Tendered, self::TeamSelected, self::TeamConfirmed, self::Prepared];
    }

    /** Anfrage und Prüfung. @return list<self> */
    public static function intake(): array
    {
        return [self::Request, self::InReview];
    }

    /** Alles zwischen Prüfung und Vorbereitung: lebende Seminare vor der Durchführung. @return list<self> */
    public static function inProgress(): array
    {
        return [self::InReview, self::QuoteSent, ...self::planning()];
    }

    /** Durchgeführt oder weiter. @return list<self> */
    public static function finished(): array
    {
        return [self::Held, self::Reported, self::Completed];
    }

    /** @param  list<self>  $cases @return list<string> */
    public static function values(array $cases): array
    {
        return array_map(fn (self $case): string => $case->value, $cases);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}

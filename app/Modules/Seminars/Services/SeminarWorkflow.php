<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarEvent;
use App\Modules\Seminars\Support\SeminarNumbers;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Die einzige Stelle, an der ein Seminar seinen Status ändert: erlaubte Übergänge, Rechte, Begründungen, Verlauf und Protokoll.
 * Controller und Ansichten rufen nur transition() oder die benannten Schritte auf.
 */
final class SeminarWorkflow
{
    /** @var array<string, list<SeminarStatus>> Von => erlaubte Ziele */
    private const GRAPH = [
        'request' => [SeminarStatus::InReview, SeminarStatus::Rejected],
        'in_review' => [SeminarStatus::QuoteSent, SeminarStatus::Rejected],
        'quote_sent' => [SeminarStatus::Released, SeminarStatus::InReview, SeminarStatus::Rejected],
        'released' => [SeminarStatus::Planned, SeminarStatus::Cancelled],
        'planned' => [SeminarStatus::Tendered, SeminarStatus::Cancelled],
        'tendered' => [SeminarStatus::TeamSelected, SeminarStatus::Cancelled],
        'team_selected' => [SeminarStatus::TeamConfirmed, SeminarStatus::Tendered, SeminarStatus::Cancelled],
        'team_confirmed' => [SeminarStatus::Prepared, SeminarStatus::Planned, SeminarStatus::Cancelled],
        'prepared' => [SeminarStatus::Held, SeminarStatus::Planned, SeminarStatus::Cancelled],
        'held' => [SeminarStatus::Reported],
        'reported' => [SeminarStatus::Completed, SeminarStatus::Held],
        'completed' => [SeminarStatus::Reported],
        'rejected' => [SeminarStatus::InReview],
        'cancelled' => [SeminarStatus::Planned, SeminarStatus::InReview],
    ];

    /** Ziele, die immer eine Begründung brauchen (zusätzlich jeder Schritt zurück im Ablauf und jedes Wiederöffnen). */
    private const REASON_FOR = [SeminarStatus::Rejected, SeminarStatus::Cancelled];

    /** @return list<SeminarStatus> */
    public static function targets(SeminarStatus $from): array
    {
        return self::GRAPH[$from->value] ?? [];
    }

    /** Gibt es diesen Übergang im Ablauf (unabhängig von den Rechten)? */
    public static function exists(SeminarStatus $from, SeminarStatus $to): bool
    {
        return in_array($to, self::targets($from), true);
    }

    /** Verlangt dieser Übergang eine Begründung? */
    public static function needsReason(SeminarStatus $from, SeminarStatus $to): bool
    {
        if (in_array($to, self::REASON_FOR, true) || $from->isEnded() || $from === SeminarStatus::Completed) {
            return true;
        }

        return ($to->position() ?? PHP_INT_MAX) < ($from->position() ?? -1);
    }

    /** Welche Berechtigungen reichen für diesen Übergang (eine davon genügt)? @return list<string> */
    public static function abilities(SeminarStatus $from, SeminarStatus $to): array
    {
        if ($from === SeminarStatus::Completed) {
            return ['seminars.reopen'];
        }

        return match ($to) {
            SeminarStatus::InReview, SeminarStatus::QuoteSent, SeminarStatus::Rejected, SeminarStatus::Released => ['seminars.review'],
            SeminarStatus::Cancelled => ['seminars.review', 'seminars.plan'],
            default => ['seminars.plan'],
        };
    }

    public function can(Seminar $seminar, SeminarStatus $to, User $by): bool
    {
        if (! self::exists($seminar->status, $to)) {
            return false;
        }

        foreach (self::abilities($seminar->status, $to) as $ability) {
            if ($by->can($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Die Übergänge, die diese Person ausführen darf und die ohne weitere Angaben gehen (die Seite bietet dafür Schaltflächen an).
     * Schritte mit eigenen Angaben (Freigabe, KVA, Team, Bericht) haben eigene Aktionen und stehen hier nicht.
     *
     * @return list<SeminarStatus>
     */
    public function simpleTargets(Seminar $seminar, User $by): array
    {
        $simple = [SeminarStatus::InReview, SeminarStatus::Rejected, SeminarStatus::Cancelled];

        return array_values(array_filter(self::targets($seminar->status), fn (SeminarStatus $to): bool => in_array($to, $simple, true) && $this->can($seminar, $to, $by)));
    }

    /**
     * Legt eine Anfrage einer Schule an (Status „Anfrage“, Nummer, Koordination der Schule).
     *
     * @param  array{title: string, format?: ?string, participants?: ?int, desired_period?: ?string, request_note?: ?string, contact_name?: ?string, contact_email?: ?string}  $data
     */
    public function request(Partner $partner, array $data, User $by): Seminar
    {
        $seminar = DB::transaction(function () use ($partner, $data, $by): Seminar {
            $seminar = Seminar::query()->create([
                'number' => SeminarNumbers::next(),
                'title' => trim($data['title']),
                'status' => SeminarStatus::Request,
                'partner_id' => $partner->id,
                'coordination_id' => $partner->coordination_id,
                'format' => $data['format'] ?? null,
                'participants' => $data['participants'] ?? null,
                'desired_period' => $data['desired_period'] ?? null,
                'request_note' => $data['request_note'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_email' => isset($data['contact_email']) ? mb_strtolower((string) $data['contact_email']) : null,
                'requested_by_user_id' => $by->id,
            ]);

            $this->log($seminar, 'requested', $by);

            return $seminar;
        });

        AuditLog::record(AuditEvent::SeminarRequested, $by, null, ['seminar' => $seminar->number]);

        return $seminar;
    }

    /**
     * Wechselt den Status, wenn der Übergang erlaubt ist, die Person das Recht hat und eine nötige Begründung vorliegt.
     *
     * @throws DomainException
     */
    public function transition(Seminar $seminar, SeminarStatus $to, User $by, ?string $comment = null): Seminar
    {
        $from = $seminar->status;

        if (! self::exists($from, $to)) {
            throw new DomainException('Von „'.$from->label().'“ geht es nicht direkt zu „'.$to->label().'“.');
        }

        if (! $this->can($seminar, $to, $by)) {
            throw new DomainException('Für diesen Schritt fehlt Ihnen die Berechtigung.');
        }

        $comment = $comment === null ? null : trim($comment);

        if (self::needsReason($from, $to) && ($comment === null || $comment === '')) {
            throw new DomainException('Bitte geben Sie eine Begründung an.');
        }

        DB::transaction(function () use ($seminar, $from, $to, $by, $comment): void {
            $seminar->status = $to;
            $seminar->save();

            $this->log($seminar, 'status', $by, $from, $to, $comment);
        });

        AuditLog::record(AuditEvent::SeminarStatusChanged, $by, null, ['seminar' => $seminar->number, 'von' => $from->value, 'nach' => $to->value]);

        return $seminar;
    }

    /** Vermerk im Verlauf des Seminars. @param  array<string, mixed>|null  $data */
    public function log(Seminar $seminar, string $type, ?User $by, ?SeminarStatus $from = null, ?SeminarStatus $to = null, ?string $comment = null, ?array $data = null): SeminarEvent
    {
        return SeminarEvent::query()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $by?->id,
            'actor_label' => $by === null ? 'automatisch' : ($by->person?->fullName() ?: (string) $by->email),
            'type' => $type,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'comment' => $comment,
            'data' => $data,
        ]);
    }
}

<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarSession;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Schulseminare anlegen, ändern, löschen, Status wechseln und Termine pflegen. Jede Änderung steht im Verlauf des Seminars. */
final class SeminarAdministration
{
    /** @param  array<string, mixed>  $data  validierte Formulardaten */
    public function create(array $data, User $by): Seminar
    {
        $seminar = DB::transaction(function () use ($data, $by): Seminar {
            $seminar = Seminar::query()->create($this->columns($data) + ['status' => SeminarStatus::Request, 'created_by_user_id' => $by->id, 'updated_by_user_id' => $by->id]);

            $this->record($seminar, SeminarEventType::Created, $by);

            return $seminar;
        });

        AuditLog::record(AuditEvent::SeminarCreated, $by, $seminar);

        return $seminar;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Seminar $seminar, array $data, User $by): Seminar
    {
        $seminar->fill($this->columns($data) + ['updated_by_user_id' => $by->id])->save();

        $changed = array_values(array_diff(array_keys($seminar->getChanges()), ['updated_at', 'updated_by_user_id']));

        if ($changed !== []) {
            $this->record($seminar, SeminarEventType::Updated, $by, null, ['felder' => $changed]);
            AuditLog::record(AuditEvent::SeminarUpdated, $by, $seminar, ['felder' => $changed]);
        }

        return $seminar;
    }

    /** @throws DomainException wenn das Seminar schon weiter ist */
    public function delete(Seminar $seminar, User $by): void
    {
        if (! in_array($seminar->status, [SeminarStatus::Request, SeminarStatus::Cancelled], true)) {
            throw new DomainException('Nur Anfragen und abgesagte Seminare lassen sich löschen. Sagen Sie das Seminar stattdessen ab.');
        }

        AuditLog::record(AuditEvent::SeminarDeleted, $by, $seminar);

        $seminar->delete();
    }

    /** @throws DomainException bei einem nicht erlaubten Wechsel */
    public function changeStatus(Seminar $seminar, SeminarStatus $target, User $by, ?string $comment = null): void
    {
        $from = $seminar->status;

        if (! $from->canChangeTo($target)) {
            throw new DomainException("Von „{$from->label()}“ lässt sich nicht zu „{$target->label()}“ wechseln.");
        }

        if (in_array($target, [SeminarStatus::Scheduled, SeminarStatus::Confirmed], true) && ! $seminar->sessions()->exists()) {
            throw new DomainException('Tragen Sie zuerst mindestens einen Termin ein.');
        }

        if ($target === SeminarStatus::Confirmed && ! $seminar->quotes()->exists()) {
            throw new DomainException('Erstellen Sie zuerst einen Kostenvoranschlag.');
        }

        $seminar->forceFill(['status' => $target, 'updated_by_user_id' => $by->id])->save();

        $this->record($seminar, SeminarEventType::StatusChanged, $by, $comment, ['von' => $from->value, 'nach' => $target->value]);
        AuditLog::record(AuditEvent::SeminarStatusChanged, $by, $seminar, ['von' => $from->label(), 'nach' => $target->label()]);

        if ($target === SeminarStatus::Cancelled) {
            app(TeamApplications::class)->notifyCancelled($seminar);
        }
    }

    /** @param  array{date: string, start: string, end: string, location?: ?string, notes?: ?string}  $data */
    public function addSession(Seminar $seminar, array $data, User $by): SeminarSession
    {
        $session = $seminar->sessions()->create([
            'starts_at' => Carbon::parse($data['date'].' '.$data['start']),
            'ends_at' => Carbon::parse($data['date'].' '.$data['end']),
            'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
        ]);

        $this->record($seminar, SeminarEventType::SessionAdded, $by, null, ['termin' => $session->starts_at->format('d.m.Y H:i')]);

        return $session;
    }

    public function removeSession(Seminar $seminar, SeminarSession $session, User $by): void
    {
        $label = $session->starts_at->format('d.m.Y H:i');
        $session->delete();

        $this->record($seminar, SeminarEventType::SessionRemoved, $by, null, ['termin' => $label]);
    }

    public function note(Seminar $seminar, string $note, User $by): void
    {
        $this->record($seminar, SeminarEventType::Note, $by, $note);
    }

    /** @param  array<string, mixed>  $data  */
    private function columns(array $data): array
    {
        $clean = static fn (mixed $value): mixed => is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;

        return [
            'title' => trim((string) $data['title']),
            'type' => $data['type'],
            'partner_id' => (int) $data['partner_id'],
            'grade_level' => $clean($data['grade_level'] ?? null),
            'expected_participants' => ($data['expected_participants'] ?? null) !== null && $data['expected_participants'] !== '' ? (int) $data['expected_participants'] : null,
            'notes' => $clean($data['notes'] ?? null),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function record(Seminar $seminar, SeminarEventType $type, ?User $by, ?string $comment = null, array $data = []): void
    {
        $seminar->events()->create([
            'type' => $type,
            'occurred_at' => now(),
            'actor_user_id' => $by?->id,
            'actor_label' => $by !== null ? AuditLog::label($by) : null,
            'comment' => filled($comment) ? $comment : null,
            'data' => $data === [] ? null : $data,
        ]);
    }
}

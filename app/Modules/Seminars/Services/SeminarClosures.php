<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarClosure;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Abschluss eines Seminars: feste Checkliste (automatisch erkannte Punkte oder bewusst übersprungen mit Begründung), Schnappschuss der Kennzahlen
 * (nur Zahlen, keine Personendaten) und Statuswechsel auf „Abgeschlossen“. Der Vorstand kann mit Begründung wieder öffnen; der Schnappschuss bleibt als Version erhalten.
 */
final class SeminarClosures
{
    /**
     * Die feste Checkliste. „auto“ heißt: Der Punkt ist durch vorhandene Daten erfüllt. Ohne „auto“ bestätigt man ihn von Hand oder begründet das Überspringen.
     *
     * @return array<string, array{label: string, auto: bool, hint: string}>
     */
    public function checklist(Seminar $seminar): array
    {
        $kinds = $seminar->documents()->pluck('kind')->unique()->all();

        return [
            'quote' => ['label' => 'Kostenvoranschlag liegt vor', 'auto' => $seminar->quotes()->exists(), 'hint' => 'Wird erkannt, sobald ein Kostenvoranschlag zum Seminar existiert.'],
            'invoice' => ['label' => 'Abrechnung oder Rechnung ist abgelegt', 'auto' => in_array('invoice', $kinds, true), 'hint' => 'Wird erkannt, wenn ein Dokument der Art „Rechnung oder Abrechnung“ abgelegt ist.'],
            'team' => ['label' => 'Das Team ist eingetragen', 'auto' => $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->exists(), 'hint' => 'Wird erkannt, wenn mindestens eine Person im Team bestätigt ist.'],
            'evaluation' => ['label' => 'Auswertung oder Ergebnis ist abgelegt', 'auto' => count(array_intersect($kinds, ['evaluation', 'result', 'report', 'statistics'])) > 0, 'hint' => 'Wird erkannt, wenn eine Auswertung, ein Ergebnis, ein Bericht oder eine Statistik abgelegt ist.'],
            'payments' => ['label' => 'Honorare und Erstattungen sind erledigt', 'auto' => false, 'hint' => 'Bitte von Hand bestätigen.'],
        ];
    }

    /**
     * Schließt das Seminar ab. Für jeden nicht erkannten Punkt muss er bestätigt oder mit Begründung übersprungen sein.
     *
     * @param  array<string, mixed>  $done  Punkt => bestätigt (von Hand)
     * @param  array<string, mixed>  $reasons  Punkt => Begründung
     *
     * @throws DomainException
     */
    public function close(Seminar $seminar, array $done, array $reasons, User $by): SeminarClosure
    {
        if ($seminar->status !== SeminarStatus::Done) {
            throw new DomainException('Nur ein durchgeführtes Seminar lässt sich abschließen.');
        }

        $result = [];
        $missing = [];

        foreach ($this->checklist($seminar) as $key => $item) {
            $confirmed = (bool) ($done[$key] ?? false);
            $reason = trim((string) ($reasons[$key] ?? ''));

            if ($item['auto']) {
                $result[$key] = ['label' => $item['label'], 'state' => 'auto'];
            } elseif ($confirmed) {
                $result[$key] = ['label' => $item['label'], 'state' => 'confirmed'];
            } elseif ($reason !== '') {
                $result[$key] = ['label' => $item['label'], 'state' => 'skipped', 'reason' => Str::limit($reason, 300, '')];
            } else {
                $missing[] = $item['label'];
            }
        }

        if ($missing !== []) {
            throw new DomainException('Bitte bestätigen Sie diese Punkte oder begründen Sie, warum sie entfallen: '.implode('; ', $missing).'.');
        }

        return DB::transaction(function () use ($seminar, $result, $by): SeminarClosure {
            $closure = $seminar->closures()->create([
                'closed_by_user_id' => $by->id,
                'closed_at' => now(),
                'checklist' => $result,
                'snapshot' => $this->snapshot($seminar),
            ]);

            app(SeminarAdministration::class)->changeStatus($seminar, SeminarStatus::Completed, $by, 'Abschluss mit Checkliste');

            // Terminübersicht und Teamübersicht als unveränderliche Schnappschüsse ablegen (ein Fehler hier verhindert den Abschluss nicht).
            foreach (array_keys(GeneratedDocuments::types()) as $type) {
                try {
                    app(GeneratedDocuments::class)->generate($seminar->fresh(), $type, 'html', $by);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
            AuditLog::record(AuditEvent::SeminarClosed, $by, $seminar, ['nummer' => (string) $seminar->number]);

            return $closure;
        });
    }

    /**
     * Öffnet einen Abschluss wieder (Vorstand). Der Schnappschuss bleibt als Version erhalten.
     *
     * @throws DomainException
     */
    public function reopen(Seminar $seminar, string $reason, User $by): void
    {
        $closure = $seminar->currentClosure();

        if ($seminar->status !== SeminarStatus::Completed || $closure === null) {
            throw new DomainException('Das Seminar ist nicht abgeschlossen.');
        }

        if (trim($reason) === '') {
            throw new DomainException('Bitte nennen Sie eine Begründung für das Wiederöffnen.');
        }

        DB::transaction(function () use ($seminar, $closure, $reason, $by): void {
            $closure->forceFill(['reopened_at' => now(), 'reopened_by_user_id' => $by->id, 'reopen_reason' => Str::limit(trim($reason), 500, '')])->save();
            $seminar->forceFill(['status' => SeminarStatus::Done, 'updated_by_user_id' => $by->id])->save();
            $seminar->events()->create(['type' => SeminarEventType::StatusChanged, 'occurred_at' => now(), 'actor_user_id' => $by->id, 'actor_label' => AuditLog::label($by), 'comment' => 'Abschluss wieder geöffnet: '.Str::limit(trim($reason), 200, ''), 'data' => ['von' => SeminarStatus::Completed->value, 'nach' => SeminarStatus::Done->value]]);
            AuditLog::record(AuditEvent::SeminarReopened, $by, $seminar, ['nummer' => (string) $seminar->number]);
        });
    }

    /**
     * Kennzahlen zum Abschlusszeitpunkt. Nur Zahlen und Titel, keine Namen von Teilnehmenden oder Teamenden.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Seminar $seminar): array
    {
        $sessions = $seminar->sessions()->get();
        $hours = $sessions->sum(fn ($session): float => max(0, $session->starts_at->diffInMinutes($session->ends_at)) / 60);
        $quote = $seminar->quotes()->orderByDesc('id')->first();

        return [
            'number' => (string) $seminar->number,
            'title' => (string) $seminar->title,
            'type' => $seminar->type->label(),
            'school' => (string) ($seminar->partner?->name ?? ''),
            'sessions' => $sessions->count(),
            'hours' => round($hours, 1),
            'year' => $sessions->first()?->starts_at->format('Y'),
            'first_day' => $sessions->first()?->starts_at->format('d.m.Y'),
            'last_day' => $sessions->last()?->ends_at->format('d.m.Y'),
            'expected_participants' => $seminar->expected_participants,
            'team_size' => $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->count(),
            'quote_total_cents' => $quote?->total_cents,
            'documents' => $seminar->documents()->get()->groupBy('kind')->map->count()->all(),
        ];
    }
}

<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Events\SeminarCompleted;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Der Ablauf eines Seminars: die einzige Stelle, die den Status ändert (docs/87_SEMINARABLAUF_UMSETZUNGSPLAN.md). Die erlaubten Übergänge stehen in
 * SeminarStatus::next(), hier stehen die Bedingungen je Zielstatus. Jeder Wechsel steht im Verlauf des Seminars und im Protokoll.
 */
final class SeminarWorkflow
{
    /**
     * @param  array{basis?: ?string, signed_at?: ?string, reason?: ?string}  $extra  Angaben zur Freigabe (basis: contract|commitment)
     *
     * @throws DomainException bei einem nicht erlaubten Wechsel oder fehlenden Bedingungen
     */
    public function change(Seminar $seminar, SeminarStatus $target, User $by, ?string $comment = null, array $extra = []): void
    {
        $from = $seminar->status;

        if (! $from->canChangeTo($target)) {
            throw new DomainException("Von „{$from->label()}“ lässt sich nicht zu „{$target->label()}“ wechseln.");
        }

        if (in_array($target, [SeminarStatus::InReview, SeminarStatus::QuoteSent, SeminarStatus::Released, SeminarStatus::Rejected], true) && ! $by->can('seminars.review')) {
            throw new DomainException('Prüfung, KVA-Versand, Freigabe und Ablehnung sind Aufgaben der Geschäftsstelle.');
        }

        $comment = filled($comment) ? trim((string) $comment) : null;
        $release = $target === SeminarStatus::Released ? $this->release($extra) : [];
        $report = $this->reportFields($seminar, $target);

        $this->require($seminar, $target, $comment);

        DB::transaction(function () use ($seminar, $target, $by, $from, $comment, $release, $report): void {
            $seminar->forceFill(['status' => $target, 'updated_by_user_id' => $by->id] + $report + $release + ($release !== [] ? ['released_by_user_id' => $by->id, 'released_at' => now()] : []))->save();

            $seminar->events()->create([
                'type' => SeminarEventType::StatusChanged,
                'occurred_at' => now(),
                'actor_user_id' => $by->id,
                'actor_label' => AuditLog::label($by),
                'comment' => $comment,
                'data' => ['von' => $from->value, 'nach' => $target->value] + ($release !== [] ? ['basis' => $release['release_basis']] : []),
            ]);

            AuditLog::record(AuditEvent::SeminarStatusChanged, $by, $seminar, ['von' => $from->label(), 'nach' => $target->label()]);
        });

        if ($target === SeminarStatus::Cancelled) {
            app(TeamApplications::class)->notifyCancelled($seminar);
        }

        app(SeminarNotifications::class)->statusChanged($seminar->fresh(), $target, $comment);

        // Nextcloud: Ordner und Zugriff entstehen mit der Teambestätigung (im Hintergrund, wiederholbar)
        if ($target === SeminarStatus::TeamConfirmed && app(\App\Modules\Seminars\Nextcloud\SeminarNextcloud::class)->enabled()) {
            \App\Modules\Seminars\Jobs\SyncSeminarFolder::dispatch($seminar->fresh());
        }

        if ($target === SeminarStatus::Completed) {
            event(new SeminarCompleted($seminar->fresh()));
        }
    }

    /**
     * Freigabe: entweder mit unterschriebenem Vertrag der Schule (Datum der Unterschrift) oder auf verbindliche Zusage der Schule (Begründung Pflicht, Vertrag folgt).
     *
     * @param  array{basis?: ?string, signed_at?: ?string, reason?: ?string}  $extra
     * @return array<string, mixed>
     *
     * @throws DomainException
     */
    private function release(array $extra): array
    {
        $basis = $extra['basis'] ?? null;

        if ($basis === 'contract') {
            if (blank($extra['signed_at'] ?? null)) {
                throw new DomainException('Bitte tragen Sie das Datum der Unterschrift des Schulvertrags ein.');
            }

            return ['release_basis' => 'contract', 'school_contract_signed_at' => Carbon::parse($extra['signed_at'])->toDateString(), 'release_reason' => null];
        }

        if ($basis === 'commitment') {
            if (blank($extra['reason'] ?? null)) {
                throw new DomainException('Bitte begründen Sie die Freigabe auf Zusage (z. B. wer wann zugesagt hat).');
            }

            return ['release_basis' => 'commitment', 'release_reason' => trim((string) $extra['reason'])];
        }

        throw new DomainException('Bitte wählen Sie, ob das Seminar mit Vertrag oder auf verbindliche Zusage der Schule freigegeben wird.');
    }

    /** Ist der aktuelle Kostenvoranschlag intern freigegeben? Eine Änderung nach der Freigabe macht sie ungültig. */
    public function quoteApproved(Seminar $seminar): bool
    {
        $latest = $seminar->quotes()->max('updated_at');

        return $seminar->quote_approved_at !== null && $latest !== null && $seminar->quote_approved_at->greaterThanOrEqualTo(Carbon::parse($latest));
    }

    /** Interne Freigabe des Kostenvoranschlags (Vorstand oder Koordination). @throws DomainException */
    public function approveQuote(Seminar $seminar, User $by): void
    {
        if (! $by->can('seminars.quote_approve')) {
            throw new DomainException('Die Freigabe des Kostenvoranschlags ist Sache von Vorstand oder Koordination.');
        }

        if (! $seminar->quotes()->exists()) {
            throw new DomainException('Erstellen Sie zuerst einen Kostenvoranschlag.');
        }

        $seminar->forceFill(['quote_approved_by_user_id' => $by->id, 'quote_approved_at' => now(), 'updated_by_user_id' => $by->id])->save();
        $seminar->events()->create(['type' => SeminarEventType::Note, 'occurred_at' => now(), 'actor_user_id' => $by->id, 'actor_label' => AuditLog::label($by), 'comment' => 'Kostenvoranschlag intern freigegeben.', 'data' => null]);
        AuditLog::record(AuditEvent::SeminarQuoteApproved, $by, $seminar);
    }

    /** Spalten, die ein Wechsel nach „Durchgeführt“ (Stichtag für die Berichte) oder „Berichtet“ setzt. @return array<string, mixed> */
    private function reportFields(Seminar $seminar, SeminarStatus $target): array
    {
        if ($target === SeminarStatus::Held) {
            $last = $seminar->sessions()->max('ends_at');

            return ['report_due_at' => ($last !== null ? Carbon::parse($last) : now())->startOfDay()->addDays((int) config('portal.seminars.report_days', 14))->toDateString(), 'report_reminded_at' => null, 'report_overdue_notified_at' => null];
        }

        return $target === SeminarStatus::Reported ? ['reported_at' => now()] : [];
    }

    /** @throws DomainException */
    private function require(Seminar $seminar, SeminarStatus $target, ?string $comment): void
    {
        if (in_array($target, [SeminarStatus::Rejected, SeminarStatus::Cancelled], true) && $comment === null) {
            throw new DomainException('Bitte nennen Sie eine Begründung.');
        }

        if ($target === SeminarStatus::QuoteSent && ! $seminar->quotes()->exists()) {
            throw new DomainException('Erstellen Sie zuerst einen Kostenvoranschlag.');
        }

        if ($target === SeminarStatus::QuoteSent && ! $this->quoteApproved($seminar)) {
            throw new DomainException('Der Kostenvoranschlag ist noch nicht intern freigegeben (Vorstand oder Koordination), oder er wurde nach der Freigabe geändert.');
        }

        if (in_array($target, [SeminarStatus::Planned, SeminarStatus::Tendered], true) && ! $seminar->sessions()->exists()) {
            throw new DomainException('Tragen Sie zuerst mindestens einen Termin ein.');
        }

        if ($target === SeminarStatus::Tendered && ((int) $seminar->team_needed <= 0 || $seminar->application_deadline === null)) {
            throw new DomainException('Für die Ausschreibung brauchen Sie die Teamgröße und den Stichtag für Interesse.');
        }

        if ($target === SeminarStatus::Prepared && $seminar->nextcloud_folder_path === null && ! $seminar->nextcloud_manual && app(\App\Modules\Seminars\Nextcloud\SeminarNextcloud::class)->enabled()) {
            throw new DomainException('Der Nextcloud-Ordner ist noch nicht angelegt. Legen Sie ihn an oder vermerken Sie, dass Sie ihn von Hand anlegen.');
        }

        if ($target === SeminarStatus::Prepared && $seminar->onboarding_at === null) {
            throw new DomainException('Planen Sie zuerst den Termin für das Onboarding des Teams.');
        }

        if ($target === SeminarStatus::Reported && ! $seminar->documents()->where('kind', 'report')->exists()) {
            throw new DomainException('Laden Sie zuerst den Ergebnisbericht hoch (Art „Bericht“).');
        }

        if ($target === SeminarStatus::Held && ($seminar->sessions()->where('starts_at', '>', now())->exists() || ! $seminar->sessions()->exists())) {
            throw new DomainException('Ein Seminar gilt erst als durchgeführt, wenn alle Termine vorbei sind.');
        }
    }
}

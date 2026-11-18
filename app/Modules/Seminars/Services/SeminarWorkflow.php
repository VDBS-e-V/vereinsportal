<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Events\SeminarCompleted;
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
        return self::GRAPH[$from->value];
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

        // Die Seminarleitung bestätigt die Durchführung und gibt den Bericht ab, auch ohne Recht zur Planung.
        if ($seminar->lead_user_id !== null && $seminar->lead_user_id === $by->id && in_array($to, [SeminarStatus::Held, SeminarStatus::Reported], true) && in_array($seminar->status, [SeminarStatus::Prepared, SeminarStatus::Held], true)) {
            return true;
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
        $this->notifications()->requested($seminar->load('partner'));

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
        $this->afterTransition($seminar, $from, $to, $comment);

        return $seminar;
    }

    /**
     * Gibt ein Seminar frei: mit unterschriebenem Schulvertrag (`contract`) oder auf verbindliche Zusage der Schule (`commitment`, Begründung Pflicht, Vertrag folgt).
     *
     * @throws DomainException
     */
    public function release(Seminar $seminar, User $by, string $basis, ?string $reason = null): Seminar
    {
        if (! in_array($basis, ['contract', 'commitment'], true)) {
            throw new DomainException('Bitte wählen Sie, ob die Freigabe mit Vertrag oder auf Zusage erfolgt.');
        }

        if ($seminar->status !== SeminarStatus::QuoteSent) {
            throw new DomainException('Freigeben lässt sich ein Seminar nur im Stand „KVA gesendet“.');
        }

        if (! $by->can('seminars.review')) {
            throw new DomainException('Für diesen Schritt fehlt Ihnen die Berechtigung.');
        }

        if (! $seminar->quotes()->whereIn('status', ['sent', 'accepted'])->exists()) {
            throw new DomainException('Es gibt keinen gesendeten Kostenvoranschlag.');
        }

        $reason = $reason === null ? null : trim($reason);

        if ($basis === 'contract' && ($seminar->school_contract_signed_on === null || ! $seminar->documents()->where('kind', 'school_contract')->exists())) {
            throw new DomainException('Für die Freigabe mit Vertrag fehlt der unterschriebene Schulvertrag mit Datum.');
        }

        if ($basis === 'commitment' && ($reason === null || $reason === '')) {
            throw new DomainException('Bitte begründen Sie die Freigabe auf Zusage (zum Beispiel: Zusage per E-Mail vom Datum, Vertrag folgt).');
        }

        DB::transaction(function () use ($seminar, $by, $basis, $reason): void {
            $seminar->fill(['status' => SeminarStatus::Released, 'release_basis' => $basis, 'release_reason' => $reason, 'released_at' => now(), 'released_by_user_id' => $by->id])->save();

            $this->log($seminar, 'status', $by, SeminarStatus::QuoteSent, SeminarStatus::Released, $basis === 'contract' ? 'Freigegeben mit unterschriebenem Schulvertrag' : 'Freigegeben auf Zusage der Schule: '.$reason);
        });

        AuditLog::record(AuditEvent::SeminarReleased, $by, null, ['seminar' => $seminar->number, 'basis' => $basis]);
        $this->notifications()->released($seminar->load('partner'));

        return $seminar;
    }

    /**
     * Plant ein freigegebenes Seminar: Termine, Sollzahl des Teams. Ein schon geplantes Seminar lässt sich hier noch ändern, solange es nicht ausgeschrieben ist.
     *
     * @param  list<array{starts_at: \DateTimeInterface, ends_at: ?\DateTimeInterface, location: ?string}>  $sessions
     *
     * @throws DomainException
     */
    public function plan(Seminar $seminar, array $sessions, int $teamTarget, User $by): Seminar
    {
        if (! $by->can('seminars.plan')) {
            throw new DomainException('Für diesen Schritt fehlt Ihnen die Berechtigung.');
        }

        if (! in_array($seminar->status, [SeminarStatus::Released, SeminarStatus::Planned], true)) {
            throw new DomainException('Planen lässt sich ein Seminar nach der Freigabe und bis zur Ausschreibung.');
        }

        if ($sessions === []) {
            throw new DomainException('Bitte tragen Sie mindestens einen Termin ein.');
        }

        if ($teamTarget < 1 || $teamTarget > 50) {
            throw new DomainException('Bitte geben Sie die Sollzahl des Teams an (1 bis 50).');
        }

        $from = $seminar->status;

        DB::transaction(function () use ($seminar, $sessions, $teamTarget, $by, $from): void {
            $seminar->sessions()->delete();

            foreach ($sessions as $session) {
                $seminar->sessions()->create($session);
            }

            $seminar->fill([
                'team_target' => $teamTarget,
                'school_year' => Seminar::schoolYearOf($sessions[0]['starts_at']),
                'coordination_id' => $seminar->coordination_id ?? $seminar->partner->coordination_id,
                'status' => SeminarStatus::Planned,
            ])->save();

            $this->log($seminar, $from === SeminarStatus::Planned ? 'plan_changed' : 'status', $by, $from === SeminarStatus::Planned ? null : $from, $from === SeminarStatus::Planned ? null : SeminarStatus::Planned, count($sessions).' Termin(e), Team von '.$teamTarget);
        });

        AuditLog::record(AuditEvent::SeminarPlanned, $by, null, ['seminar' => $seminar->number, 'termine' => count($sessions), 'team' => $teamTarget]);

        return $seminar;
    }

    /**
     * Schreibt das Seminar für das Team aus. Die Bewerbungsfrist liegt vor dem ersten Termin.
     *
     * @throws DomainException
     */
    public function tender(Seminar $seminar, \DateTimeInterface $deadline, User $by): Seminar
    {
        $first = $seminar->sessions()->min('starts_at');

        if ($first === null || $seminar->team_target === null) {
            throw new DomainException('Planen Sie zuerst Termine und Sollzahl.');
        }

        if ($deadline->format('Y-m-d') < now()->toDateString()) {
            throw new DomainException('Die Bewerbungsfrist darf nicht in der Vergangenheit liegen.');
        }

        if ($deadline->format('Y-m-d') >= substr((string) $first, 0, 10)) {
            throw new DomainException('Die Bewerbungsfrist muss vor dem ersten Termin enden.');
        }

        DB::transaction(function () use ($seminar, $deadline, $by): void {
            $seminar->forceFill(['application_deadline' => $deadline->format('Y-m-d')])->save();
            $this->transition($seminar, SeminarStatus::Tendered, $by);
        });

        return $seminar;
    }

    /** Die Schule lehnt den Kostenvoranschlag ab: das Seminar ist abgelehnt (Recht der Schule, daher ohne Prüfung der Geschäftsstellen-Rechte). */
    public function declinedBySchool(Seminar $seminar, User $by, ?string $note): void
    {
        if ($seminar->status !== SeminarStatus::QuoteSent) {
            throw new DomainException('Der Kostenvoranschlag lässt sich nur im Stand „KVA gesendet“ ablehnen.');
        }

        $comment = trim('Die Schule hat den Kostenvoranschlag abgelehnt. '.trim((string) $note));

        DB::transaction(function () use ($seminar, $by, $comment): void {
            $seminar->status = SeminarStatus::Rejected;
            $seminar->save();

            $this->log($seminar, 'status', $by, SeminarStatus::QuoteSent, SeminarStatus::Rejected, $comment);
        });

        AuditLog::record(AuditEvent::SeminarStatusChanged, $by, null, ['seminar' => $seminar->number, 'von' => SeminarStatus::QuoteSent->value, 'nach' => SeminarStatus::Rejected->value]);
        $this->notifications()->schoolAnswered($seminar->load('partner'), 'Kostenvoranschlag abgelehnt'.(filled($note) ? ' („'.trim((string) $note).'“)' : ''));
    }

    /** Das Seminar ist vorbereitet: Team bestätigt, Seminarleitung bestimmt und Onboarding-Termin gesetzt. @throws DomainException */
    public function prepare(Seminar $seminar, User $by): Seminar
    {
        if ($seminar->lead_user_id === null) {
            throw new DomainException('Bestimmen Sie zuerst die Seminarleitung.');
        }

        if ($seminar->onboarding_at === null) {
            throw new DomainException('Legen Sie zuerst den Termin für das Onboarding fest.');
        }

        if (app(SeminarNextcloud::class)->enabled() && $seminar->nextcloud_path === null && ! $seminar->nextcloud_manual) {
            throw new DomainException('Der Nextcloud-Ordner fehlt noch. Legen Sie ihn mit „Ordner anlegen“ an oder vermerken Sie, dass er von Hand angelegt wird.');
        }

        return $this->transition($seminar, SeminarStatus::Prepared, $by);
    }

    /**
     * Die Seminarleitung (oder die Koordination) bestätigt die Durchführung, frühestens nach dem Beginn des letzten Termins. Der Stichtag für Bericht und Verträge folgt.
     *
     * @throws DomainException
     */
    public function markHeld(Seminar $seminar, User $by): Seminar
    {
        $last = $seminar->sessions()->max('starts_at');

        if ($last === null || strtotime((string) $last) > time()) {
            throw new DomainException('Die Durchführung lässt sich nach dem Beginn des letzten Termins bestätigen.');
        }

        DB::transaction(function () use ($seminar, $by, $last): void {
            $this->transition($seminar, SeminarStatus::Held, $by);
            $seminar->forceFill(['held_at' => now(), 'report_due_on' => date('Y-m-d', strtotime((string) $last) + 86400 * (int) config('portal.seminars.report_days', 14))])->save();
        });

        return $seminar;
    }

    /**
     * Gibt Bericht und Verträge ab: der Ergebnisbericht muss hochgeladen sein und für jede zugesagte Person gibt es einen Vertrag.
     *
     * @throws DomainException
     */
    public function submitReport(Seminar $seminar, User $by): Seminar
    {
        if (! $seminar->documents()->where('kind', 'report')->exists()) {
            throw new DomainException('Bitte laden Sie zuerst den Ergebnisbericht hoch.');
        }

        $missing = $seminar->applications()->where('outcome', 'confirmed')->whereNotIn('user_id', $seminar->paymentOrders()->select('user_id'))->with('user.person')->get()
            ->map(fn ($application): string => $application->user->person?->fullName() ?: (string) $application->user->email)->all();

        if ($missing !== []) {
            throw new DomainException('Es fehlt noch der Vertrag von: '.implode(', ', $missing).'.');
        }

        DB::transaction(function () use ($seminar, $by): void {
            $this->transition($seminar, SeminarStatus::Reported, $by);
            $seminar->forceFill(['reported_at' => now()])->save();
        });

        return $seminar;
    }

    /**
     * Sendet Bericht und Ergebnislink an die Schule. Der Link läuft frühestens ein Jahr nach dem letzten Termin ab.
     *
     * @throws DomainException
     */
    public function sendResults(Seminar $seminar, ?string $url, \DateTimeInterface $expires, User $by): Seminar
    {
        if (! $by->can('seminars.plan')) {
            throw new DomainException('Für diesen Schritt fehlt Ihnen die Berechtigung.');
        }

        if ($seminar->status !== SeminarStatus::Reported) {
            throw new DomainException('Den Bericht senden Sie, nachdem die Seminarleitung ihn abgegeben hat.');
        }

        $last = $seminar->sessions()->max('starts_at');
        $minimum = date('Y-m-d', strtotime('+'.(int) config('portal.seminars.results_min_years', 1).' year', strtotime((string) $last)));

        if ($expires->format('Y-m-d') < $minimum) {
            throw new DomainException('Der Ergebnislink muss mindestens bis '.date('d.m.Y', strtotime($minimum)).' erreichbar bleiben (ein Jahr nach dem letzten Termin).');
        }

        if (blank($url)) {
            try {
                $share = app(SeminarNextcloud::class)->share($seminar, $expires);
            } catch (\RuntimeException $exception) {
                throw new DomainException($exception->getMessage());
            }

            if ($share === null) {
                throw new DomainException('Bitte geben Sie den Link zum Ergebnisordner an.');
            }

            $url = $share['url'];
        }

        $seminar->forceFill(['results_url' => $url, 'results_expires_on' => $expires->format('Y-m-d'), 'report_sent_at' => now()])->save();
        $this->log($seminar, 'results_sent', $by, null, null, 'Bericht und Ergebnislink an die Schule gesendet (Link bis '.$expires->format('d.m.Y').')');
        $this->notifications()->results($seminar->loadMissing('partner'));

        return $seminar;
    }

    /**
     * Verlängert die Erreichbarkeit des Ergebnislinks (nie verkürzen). Gibt es einen Nextcloud-Link, wird das Ablaufdatum dort mit geändert.
     *
     * @throws DomainException
     */
    public function extendResults(Seminar $seminar, \DateTimeInterface $expires, User $by): Seminar
    {
        if (! $by->can('seminars.plan')) {
            throw new DomainException('Für diesen Schritt fehlt Ihnen die Berechtigung.');
        }

        if ($seminar->report_sent_at === null || $seminar->results_expires_on === null) {
            throw new DomainException('Es gibt noch keinen Ergebnislink.');
        }

        if ($expires->format('Y-m-d') <= $seminar->results_expires_on->format('Y-m-d')) {
            throw new DomainException('Das neue Datum muss nach dem bisherigen ('.$seminar->results_expires_on->format('d.m.Y').') liegen.');
        }

        try {
            app(SeminarNextcloud::class)->share($seminar, $expires);
        } catch (\RuntimeException $exception) {
            throw new DomainException($exception->getMessage());
        }

        $seminar->forceFill(['results_expires_on' => $expires->format('Y-m-d')])->save();
        $this->log($seminar, 'results_extended', $by, null, null, 'Ergebnislink verlängert bis '.$expires->format('d.m.Y'));

        return $seminar;
    }

    /** Schließt das Seminar ab (nach gesendetem Bericht) und löst das Ereignis SeminarCompleted aus. @throws DomainException */
    public function complete(Seminar $seminar, User $by): Seminar
    {
        if ($seminar->report_sent_at === null) {
            throw new DomainException('Senden Sie zuerst Bericht und Ergebnislink an die Schule.');
        }

        DB::transaction(function () use ($seminar, $by): void {
            $this->transition($seminar, SeminarStatus::Completed, $by);
            $seminar->forceFill(['completed_at' => now()])->save();
        });

        AuditLog::record(AuditEvent::SeminarCompleted, $by, null, ['seminar' => $seminar->number]);
        SeminarCompleted::dispatch($seminar);

        return $seminar;
    }

    /** Mitteilungen und Folgen eines Statuswechsels (nach dem Speichern). */
    private function afterTransition(Seminar $seminar, SeminarStatus $from, SeminarStatus $to, ?string $comment): void
    {
        $seminar->loadMissing('partner');
        $notifications = $this->notifications();

        if ($to === SeminarStatus::Rejected) {
            $notifications->rejected($seminar, $comment);
        } elseif ($to === SeminarStatus::Tendered && $from === SeminarStatus::Planned) {
            $notifications->tendered($seminar);
        } elseif ($to === SeminarStatus::Cancelled) {
            $notifications->cancelled($seminar, $comment);
        } elseif ($to === SeminarStatus::Planned && in_array($from, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared, SeminarStatus::Cancelled], true)) {
            // Neuer Termin: erst informieren (die Ergebnisse sind noch da), dann das Team zurücksetzen und neu anfragen.
            $notifications->rescheduled($seminar, $comment, $seminar->applications()->whereNotNull('outcome')->exists());
            $this->resetTeam($seminar);
        }
    }

    /** Setzt die Auswahl zurück: alle Meldungen sind wieder offen, Leitung, Bestätigung, Frist und Onboarding sind leer. */
    private function resetTeam(Seminar $seminar): void
    {
        $seminar->applications()->where('status', 'applied')->update(['decision' => null, 'outcome' => null, 'waitlist_position' => null, 'notified_at' => null]);
        $seminar->forceFill(['lead_user_id' => null, 'team_confirmed_at' => null, 'team_confirmed_by_user_id' => null, 'application_deadline' => null, 'onboarding_at' => null, 'onboarding_link' => null, 'tender_reminded_at' => null])->save();
    }

    private function notifications(): SeminarNotifications
    {
        return app(SeminarNotifications::class);
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

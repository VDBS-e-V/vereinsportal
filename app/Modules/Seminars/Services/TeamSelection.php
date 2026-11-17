<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Teamauswahl als Entwurf (docs/87, Phase 3): Die Koordination markiert je Interessent „Zusage“ oder „Absage“ (Entwurf, nichts wird gesendet). Erst „Team bestätigen“
 * setzt alle Entscheidungen um und sendet die Mails. Wer ohne Markierung bleibt, kommt auf die Warteliste und kann nachrücken.
 */
final class TeamSelection
{
    public const CONFIRM = 'confirm';

    public const DECLINE = 'decline';

    public function __construct(private readonly TeamApplications $team, private readonly SeminarWorkflow $workflow) {}

    /** Zusagen im Entwurf. */
    public function draftCount(Seminar $seminar): int
    {
        return $seminar->applications()->where('status', ApplicationStatus::Applied->value)->where('decision_draft', self::CONFIRM)->count();
    }

    /**
     * Entwurf setzen oder zurücknehmen ($draft = null).
     *
     * @throws DomainException
     */
    public function mark(SeminarApplication $application, ?string $draft, User $by): void
    {
        $seminar = $application->seminar;

        if (! in_array($seminar->status, [SeminarStatus::Tendered, SeminarStatus::TeamSelected], true)) {
            throw new DomainException('Die Auswahl ist nur im Status „Ausgeschrieben“ oder „Team gewählt“ möglich.');
        }

        if (! in_array($draft, [self::CONFIRM, self::DECLINE, null], true)) {
            throw new DomainException('Ungültige Entscheidung.');
        }

        if ($application->status !== ApplicationStatus::Applied) {
            throw new DomainException('Über diese Person ist schon entschieden oder sie hat ihr Interesse zurückgezogen.');
        }

        if (! $application->isConfirmedApplicant()) {
            throw new DomainException('Die E-Mail-Adresse der Person ist noch nicht bestätigt.');
        }

        if ($seminar->application_deadline !== null && ! $seminar->deadlinePassed()) {
            throw new DomainException('Der Stichtag ('.$seminar->application_deadline->format('d.m.Y').') ist noch nicht erreicht. Die Koordination sichtet die Interessenbekundungen danach.');
        }

        if ($draft === self::CONFIRM && $application->decision_draft !== self::CONFIRM && $this->draftCount($seminar) >= (int) $seminar->team_needed) {
            throw new DomainException('Die Sollzahl ist im Entwurf erreicht. Erhöhen Sie zuerst die Teamgröße.');
        }

        $application->forceFill(['decision_draft' => $draft])->save();
        $this->syncStatus($seminar->fresh(), $by);
    }

    /**
     * „Team bestätigen“: setzt den Entwurf um und sendet alle Mails zugleich (Zusagen, Absagen, Warteliste).
     *
     * @param  bool  $acceptShortfall  die Zahl der Zusagen liegt unter der Sollzahl und das ist gewollt
     * @return array{confirmed: int, declined: int, waitlisted: int}
     *
     * @throws DomainException
     */
    public function confirmTeam(Seminar $seminar, User $by, bool $acceptShortfall = false): array
    {
        if ($seminar->status !== SeminarStatus::TeamSelected) {
            throw new DomainException('Das Team lässt sich nur im Status „Team gewählt“ bestätigen.');
        }

        $applications = $seminar->applications()->where('status', ApplicationStatus::Applied->value)->orderBy('id')->get()->filter(fn (SeminarApplication $application): bool => $application->isConfirmedApplicant())->values();
        $chosen = $applications->where('decision_draft', self::CONFIRM)->values();

        if ($chosen->isEmpty()) {
            throw new DomainException('Es ist noch niemand als Zusage markiert.');
        }

        if (! $chosen->contains(fn (SeminarApplication $application): bool => $application->is_lead)) {
            throw new DomainException('Bestimmen Sie vor der Bestätigung die Seminarleitung aus dem Team.');
        }

        if ($chosen->count() < (int) $seminar->team_needed && ! $acceptShortfall) {
            throw new DomainException('Es sind '.$chosen->count().' von '.(int) $seminar->team_needed.' Plätzen besetzt. Bestätigen Sie ausdrücklich, dass das Team kleiner bleibt.');
        }

        $declined = $applications->where('decision_draft', self::DECLINE)->values();
        $waitlist = $applications->whereNull('decision_draft')->values();

        DB::transaction(function () use ($seminar, $by, $chosen, $declined, $waitlist): void {
            foreach ($chosen as $application) {
                $application->forceFill(['status' => ApplicationStatus::Confirmed, 'decision_draft' => null, 'waitlist_position' => null, 'decided_by_user_id' => $by->id, 'decided_at' => now(), 'confirmation_sent_at' => now()])->save();
                $this->event($seminar, SeminarEventType::TeamConfirmed, $by, $application->applicantName());
            }

            foreach ($declined as $application) {
                $application->forceFill(['status' => ApplicationStatus::Declined, 'decision_draft' => null, 'waitlist_position' => null, 'decided_by_user_id' => $by->id, 'decided_at' => now(), 'confirmation_sent_at' => now()])->save();
                $this->event($seminar, SeminarEventType::TeamDeclined, $by, $application->applicantName());
            }

            foreach ($waitlist as $position => $application) {
                $application->forceFill(['status' => ApplicationStatus::Declined, 'decision_draft' => null, 'waitlist_position' => $position + 1, 'decided_by_user_id' => $by->id, 'decided_at' => now(), 'confirmation_sent_at' => now()])->save();
                $this->event($seminar, SeminarEventType::TeamDeclined, $by, $application->applicantName().' (Warteliste '.($position + 1).')');
            }

            $seminar->forceFill(['team_confirmed_at' => now(), 'team_confirmed_by_user_id' => $by->id])->save();
            $this->workflow->change($seminar, SeminarStatus::TeamConfirmed, $by, 'Team bestätigt: '.$chosen->count().' Zusagen, '.$declined->count().' Absagen, '.$waitlist->count().' auf der Warteliste.');
            AuditLog::record(AuditEvent::SeminarTeamDecided, $by, $seminar, ['entscheidung' => 'Team bestätigt', 'zusagen' => $chosen->count()]);
        });

        // Alle Mails erst nach dem Speichern
        foreach ($chosen as $application) {
            $this->team->notify($application, 'confirmed', $seminar);
        }

        foreach ($declined as $application) {
            $this->team->notify($application, 'declined', $seminar);
        }

        foreach ($waitlist as $application) {
            $this->team->notify($application, 'waitlist', $seminar);
        }

        return ['confirmed' => $chosen->count(), 'declined' => $declined->count(), 'waitlisted' => $waitlist->count()];
    }

    /**
     * Nachrücken von der Warteliste, wenn im bestätigten Team ein Platz frei ist (Pflicht-Mail an die Person).
     *
     * @throws DomainException
     */
    public function promote(SeminarApplication $application, User $by): void
    {
        $seminar = $application->seminar;

        if (! in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true)) {
            throw new DomainException('Nachrücken ist nur nach der Teambestätigung und vor dem Seminar möglich.');
        }

        if ($application->status !== ApplicationStatus::Declined || $application->waitlist_position === null) {
            throw new DomainException('Die Person steht nicht auf der Warteliste.');
        }

        if ($this->team->confirmedCount($seminar) >= (int) $seminar->team_needed) {
            throw new DomainException('Es ist kein Platz frei. Erhöhen Sie zuerst die Teamgröße oder lassen Sie jemanden ausscheiden.');
        }

        DB::transaction(function () use ($application, $seminar, $by): void {
            $application->forceFill(['status' => ApplicationStatus::Confirmed, 'waitlist_position' => null, 'decided_by_user_id' => $by->id, 'decided_at' => now(), 'confirmation_sent_at' => now()])->save();
            $position = 0;

            foreach ($seminar->applications()->whereNotNull('waitlist_position')->orderBy('waitlist_position')->get() as $waiting) {
                $waiting->forceFill(['waitlist_position' => ++$position])->save();
            }

            $this->event($seminar, SeminarEventType::TeamConfirmed, $by, $application->applicantName().' (nachgerückt)');
            AuditLog::record(AuditEvent::SeminarTeamDecided, $by, $seminar, ['entscheidung' => 'Nachgerückt']);
        });

        $this->team->notify($application->fresh(), 'confirmed', $seminar);
    }

    /** Der Status folgt dem Entwurf: mit mindestens einer Zusage „Team gewählt“, sonst wieder „Ausgeschrieben“. */
    private function syncStatus(Seminar $seminar, User $by): void
    {
        $count = $this->draftCount($seminar);

        if ($seminar->status === SeminarStatus::Tendered && $count > 0) {
            $this->workflow->change($seminar, SeminarStatus::TeamSelected, $by, 'Auswahl im Entwurf begonnen.');
        } elseif ($seminar->status === SeminarStatus::TeamSelected && $count === 0) {
            $this->workflow->change($seminar, SeminarStatus::Tendered, $by, 'Keine Zusage mehr im Entwurf.');
        }
    }

    private function event(Seminar $seminar, SeminarEventType $type, User $by, string $comment): void
    {
        $seminar->events()->create(['type' => $type, 'occurred_at' => now(), 'actor_user_id' => $by->id, 'actor_label' => AuditLog::label($by), 'comment' => $comment]);
    }
}

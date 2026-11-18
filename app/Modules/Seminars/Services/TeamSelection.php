<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Auswahl des Teams: Zusage und Absage als Entwurf, „Team bestätigen“ setzt alles um und sendet alle Mails zugleich,
 * danach Warteliste, Nachrücken, Ausscheiden und Seminarleitung. Zusage, Absage und Warteliste sind Pflicht-Mails.
 */
final class TeamSelection
{
    public const CONFIRM = 'confirm';

    public const DECLINE = 'decline';

    public function __construct(private readonly SeminarWorkflow $workflow) {}

    /**
     * Zusage, Absage oder zurücksetzen (null) als Entwurf. Nichts wird gesendet. Die erste Zusage setzt das Seminar auf „Team gewählt“.
     *
     * @throws DomainException
     */
    public function draft(Seminar $seminar, SeminarApplication $application, ?string $decision, User $by): void
    {
        $this->assertPlanner($by);

        if (! in_array($seminar->status, [SeminarStatus::Tendered, SeminarStatus::TeamSelected], true)) {
            throw new DomainException('Die Auswahl lässt sich nur treffen, solange das Seminar ausgeschrieben ist und das Team nicht bestätigt wurde.');
        }

        if ($application->seminar_id !== $seminar->id || $application->status !== SeminarApplication::APPLIED) {
            throw new DomainException('Diese Meldung ist nicht mehr gültig.');
        }

        if ($decision !== null && ! in_array($decision, [self::CONFIRM, self::DECLINE], true)) {
            throw new DomainException('Unbekannte Entscheidung.');
        }

        $application->update(['decision' => $decision]);

        $confirms = $seminar->applications()->where('status', SeminarApplication::APPLIED)->where('decision', self::CONFIRM)->count();

        if ($seminar->status === SeminarStatus::Tendered && $confirms > 0) {
            $this->workflow->transition($seminar, SeminarStatus::TeamSelected, $by);
        } elseif ($seminar->status === SeminarStatus::TeamSelected && $confirms === 0) {
            $this->workflow->transition($seminar, SeminarStatus::Tendered, $by, 'Keine Zusage mehr im Entwurf');
        }
    }

    /**
     * „Team bestätigen“: setzt die Entwürfe um, bestimmt die Seminarleitung und sendet alle Mails. Wer ohne Entscheidung bleibt, kommt auf die Warteliste.
     * Sind es weniger Zusagen als die Sollzahl, braucht es die ausdrückliche Bestätigung.
     *
     * @throws DomainException
     */
    public function confirm(Seminar $seminar, User $by, int $leadUserId, bool $acknowledged): void
    {
        $this->assertPlanner($by);

        if ($seminar->status !== SeminarStatus::TeamSelected) {
            throw new DomainException('Bestätigen lässt sich ein Team, wenn mindestens eine Zusage im Entwurf steht.');
        }

        $applications = $seminar->applications()->where('status', SeminarApplication::APPLIED)->with('user.person')->get();
        $confirms = $applications->where('decision', self::CONFIRM);

        if ($confirms->isEmpty()) {
            throw new DomainException('Es gibt keine Zusage im Entwurf.');
        }

        if (! $confirms->contains(fn (SeminarApplication $application): bool => $application->user_id === $leadUserId)) {
            throw new DomainException('Die Seminarleitung muss eine Person mit Zusage sein.');
        }

        if ($seminar->team_target !== null && $confirms->count() < $seminar->team_target && ! $acknowledged) {
            throw new DomainException('Es sind weniger Zusagen ('.$confirms->count().') als die Sollzahl ('.$seminar->team_target.'). Bestätigen Sie ausdrücklich, dass Sie trotzdem fortfahren.');
        }

        DB::transaction(function () use ($seminar, $by, $applications, $leadUserId): void {
            $position = 0;

            foreach ($applications as $application) {
                if ($application->decision === self::CONFIRM) {
                    $application->update(['outcome' => 'confirmed', 'waitlist_position' => null]);
                } elseif ($application->decision === self::DECLINE) {
                    $application->update(['outcome' => 'declined', 'waitlist_position' => null]);
                } else {
                    $application->update(['outcome' => 'waitlist', 'waitlist_position' => ++$position]);
                }
            }

            $seminar->forceFill(['lead_user_id' => $leadUserId, 'team_confirmed_at' => now(), 'team_confirmed_by_user_id' => $by->id])->save();
            $this->workflow->transition($seminar, SeminarStatus::TeamConfirmed, $by);
        });

        AuditLog::record(AuditEvent::SeminarTeamConfirmed, $by, null, ['seminar' => $seminar->number, 'zusagen' => $confirms->count()]);

        $sent = 0;

        foreach ($applications->fresh() ?? [] as $application) {
            $sent += $this->notify($seminar, $application) ? 1 : 0;
        }

        $this->workflow->log($seminar, 'mail', $by, null, null, 'Zusagen, Absagen und Wartelistenplätze gesendet: '.$sent.' Mail(s)');
    }

    /**
     * Rückt eine Person von der Warteliste nach (Zusage per Mail). @throws DomainException
     */
    public function promote(Seminar $seminar, SeminarApplication $application, User $by): void
    {
        $this->assertPlanner($by);
        $this->assertTeamPhase($seminar, $application);

        if ($application->outcome !== 'waitlist') {
            throw new DomainException('Nur Personen von der Warteliste lassen sich nachrücken.');
        }

        DB::transaction(function () use ($seminar, $application): void {
            $application->update(['outcome' => 'confirmed', 'waitlist_position' => null]);
            $this->renumberWaitlist($seminar);
        });

        $this->workflow->log($seminar, 'team_promoted', $by, null, null, $application->user->person?->fullName() ?: (string) $application->user->email);
        $this->notify($seminar, $application->fresh() ?? $application);
    }

    /**
     * Eine zugesagte Person scheidet aus (Begründung Pflicht). War sie die Seminarleitung, muss eine neue bestimmt werden. @throws DomainException
     */
    public function drop(Seminar $seminar, SeminarApplication $application, User $by, string $reason): void
    {
        $this->assertPlanner($by);
        $this->assertTeamPhase($seminar, $application);

        if ($application->outcome !== 'confirmed') {
            throw new DomainException('Nur zugesagte Personen können ausscheiden.');
        }

        if (trim($reason) === '') {
            throw new DomainException('Bitte geben Sie eine Begründung an.');
        }

        DB::transaction(function () use ($seminar, $application): void {
            $application->update(['outcome' => 'dropped']);

            if ($seminar->lead_user_id === $application->user_id) {
                $seminar->forceFill(['lead_user_id' => null])->save();
            }
        });

        $this->workflow->log($seminar, 'team_dropped', $by, null, null, ($application->user->person?->fullName() ?: (string) $application->user->email).': '.trim($reason));
    }

    /** Bestimmt die Seminarleitung neu (aus den zugesagten Personen). @throws DomainException */
    public function setLead(Seminar $seminar, int $userId, User $by): void
    {
        $this->assertPlanner($by);

        $application = $seminar->applications()->where('user_id', $userId)->where('outcome', 'confirmed')->first();

        if ($application === null) {
            throw new DomainException('Die Seminarleitung muss eine Person mit Zusage sein.');
        }

        if (! in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true)) {
            throw new DomainException('Die Seminarleitung lässt sich nur zwischen Teambestätigung und Durchführung ändern.');
        }

        $seminar->forceFill(['lead_user_id' => $userId])->save();
        $this->workflow->log($seminar, 'team_lead', $by, null, null, $application->user->person?->fullName() ?: (string) $application->user->email);
    }

    /** Sendet die Pflicht-Mail zum Ergebnis dieser Person (Zusage, Absage, Warteliste). Ein Fehler beim Senden bricht nichts ab. */
    private function notify(Seminar $seminar, SeminarApplication $application): bool
    {
        $user = $application->user;
        $dates = $seminar->sessions()->get()->map(fn ($session): string => $session->starts_at->format('d.m.Y, H:i').' Uhr'.(filled($session->location) ? ', '.$session->location : ''))->all();
        $name = (string) ($user->person?->fullName() ?: '');
        $url = route('seminars.team.index');

        $mail = match ($application->outcome) {
            'confirmed' => new SeminarNoticeMail('Zusage: '.$seminar->title, $name, array_filter([
                'wir freuen uns, dass Sie beim Seminar „'.$seminar->title.'“ ('.$seminar->partner->name.') dabei sind.',
                $dates === [] ? null : 'Termine: '.implode('; ', $dates).'.',
                $seminar->lead_user_id === $user->id ? 'Die Koordination hat Sie als Seminarleitung bestimmt.' : null,
            ]), $url, 'Seminar im Portal ansehen'),
            'declined' => new SeminarNoticeMail('Absage: '.$seminar->title, $name, ['leider können wir Sie für das Seminar „'.$seminar->title.'“ diesmal nicht berücksichtigen. Wir freuen uns, wenn Sie sich auf spätere Seminare melden.'], $url, 'Ausgeschriebene Seminare ansehen'),
            'waitlist' => new SeminarNoticeMail('Warteliste: '.$seminar->title, $name, ['Sie stehen für das Seminar „'.$seminar->title.'“ auf der Warteliste (Platz '.$application->waitlist_position.'). Sagt jemand aus dem Team ab, rücken Sie nach und erhalten eine Zusage.'], $url),
            default => null,
        };

        if ($mail === null || blank($user->email)) {
            return false;
        }

        try {
            Mail::to((string) $user->email)->send($mail);
            $application->forceFill(['notified_at' => now()])->save();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function renumberWaitlist(Seminar $seminar): void
    {
        $position = 0;

        foreach ($seminar->applications()->where('outcome', 'waitlist')->orderBy('waitlist_position')->orderBy('id')->get() as $application) {
            $application->update(['waitlist_position' => ++$position]);
        }
    }

    /** @throws DomainException */
    private function assertPlanner(User $by): void
    {
        if (! $by->can('seminars.plan')) {
            throw new DomainException('Für diesen Schritt fehlt Ihnen die Berechtigung.');
        }
    }

    /** @throws DomainException */
    private function assertTeamPhase(Seminar $seminar, SeminarApplication $application): void
    {
        if (! in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true) || $application->seminar_id !== $seminar->id) {
            throw new DomainException('Das Team lässt sich nur zwischen Teambestätigung und Durchführung ändern.');
        }
    }
}

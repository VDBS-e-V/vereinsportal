<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Alle Mitteilungen zum Seminarablauf. Immer per E-Mail; Personen mit Konto schalten abschaltbare Mitteilungen unter „Benachrichtigungen“ ab.
 * Nicht abschaltbar sind: Kostenvoranschlag, Zusage, Absage und Warteliste (siehe SeminarQuoteMail und TeamSelection) sowie alles an die Schule,
 * die kein Konto-Einstellung hat. Ein Fehler beim Senden bricht nie den Ablauf ab.
 */
final class SeminarNotifications
{
    public function __construct(private readonly NotificationPreferences $preferences) {}

    /** Anfrage eingegangen: Bestätigung an die Schule, Hinweis an die Geschäftsstelle. */
    public function requested(Seminar $seminar, bool $confirmToSchool = true): void
    {
        if ($confirmToSchool) {
            $this->toSchool($seminar, 'requested_school', $this->base($seminar));
        }

        $this->toCapable(Capability::SeminarsReview, 'seminar_requests', $confirmToSchool ? 'requested_office' : 'entered_office', $this->base($seminar), route('seminars.office.show', $seminar), 'Anfrage ansehen');
    }

    /** Die Schule hat zum Kostenvoranschlag etwas geschrieben (Frage, Annahme, Ablehnung). */
    public function schoolAnswered(Seminar $seminar, string $what): void
    {
        $this->toCapable(Capability::SeminarsReview, 'seminar_requests', 'answered', $this->base($seminar) + ['antwort' => $what], route('seminars.office.show', $seminar), 'Seminar ansehen');
    }

    /** Anfrage abgelehnt: Begründung an die Schule. */
    public function rejected(Seminar $seminar, ?string $reason): void
    {
        $this->toSchool($seminar, 'rejected', $this->base($seminar) + ['begruendung' => (string) $reason]);
    }

    /** Seminar freigegeben: die Koordination kann planen. */
    public function released(Seminar $seminar): void
    {
        $this->toCapable(Capability::SeminarsPlan, 'seminar_released', 'released', $this->base($seminar), route('seminars.plan.show', $seminar), 'Seminar planen');
    }

    /** Ausschreibung: Hinweis an alle Teamenden, die sich noch nicht gemeldet haben. */
    public function tendered(Seminar $seminar): void
    {
        $applied = $seminar->applications()->pluck('user_id')->all();
        $first = $seminar->sessions()->min('starts_at');

        $this->toCapable(Capability::SeminarsApply, 'seminar_tender', 'tendered', $this->base($seminar) + ['erster_termin' => $first ? date('d.m.Y', (int) strtotime((string) $first)) : '', 'frist' => (string) $seminar->application_deadline?->format('d.m.Y')], route('seminars.team.index'), 'Interesse melden', $applied);
    }

    /** Erinnerung kurz vor Fristende an Teamende, die sich noch nicht gemeldet haben. */
    public function deadlineNear(Seminar $seminar): void
    {
        $this->toCapable(Capability::SeminarsApply, 'seminar_tender', 'deadline_near', $this->base($seminar) + ['frist' => (string) $seminar->application_deadline?->format('d.m.Y')], route('seminars.team.index'), 'Interesse melden', $seminar->applications()->pluck('user_id')->all());
    }

    /** Seminar abgesagt: Team und Schule erfahren es mit der Begründung. */
    public function cancelled(Seminar $seminar, ?string $reason): void
    {
        $values = $this->base($seminar) + ['begruendung' => (string) $reason];

        $this->toSchool($seminar, 'cancelled', $values);
        $this->toTeam($seminar, 'cancelled', $values);
    }

    /** Neuer Termin: Team und Schule erfahren es, das Team wird neu angefragt. */
    public function rescheduled(Seminar $seminar, ?string $reason, bool $teamReset): void
    {
        $values = $this->base($seminar) + ['grund' => (string) $reason];

        $this->toSchool($seminar, 'rescheduled', $values);

        if ($teamReset) {
            $this->toTeam($seminar, 'rescheduled_team', $values);
        }
    }

    /** Einladung zum Onboarding (Termin, ggf. Link der Videokonferenz) an das bestätigte Team. */
    public function onboardingInvitation(Seminar $seminar): void
    {
        $this->toTeam($seminar, 'onboarding', $this->base($seminar) + ['termin' => (string) $seminar->onboarding_at?->format('d.m.Y, H:i'), 'onboarding_link' => (string) $seminar->onboarding_link]);
    }

    /** Briefing an die Schule: Ablauf, Raum, Material. Der Text kommt von der Koordination. */
    public function briefing(Seminar $seminar, string $text): void
    {
        $this->toSchool($seminar, 'briefing', $this->base($seminar) + ['text' => '<p>'.str_replace(["\r\n", "\r", "\n"], '<br>', e(trim($text))).'</p>']);
    }

    /** Bericht und Ergebnislink an die Schule. */
    public function results(Seminar $seminar): void
    {
        $this->toSchool($seminar, 'results', $this->base($seminar) + ['ergebnislink' => (string) $seminar->results_url, 'ergebnis_bis' => (string) $seminar->results_expires_on?->format('d.m.Y')]);
    }

    /** Der Ergebnislink läuft bald ab: die Koordination kann ihn verlängern. */
    public function resultsExpiring(Seminar $seminar): void
    {
        $this->toCapable(Capability::SeminarsPlan, 'seminar_overdue', 'results_expiring', $this->base($seminar) + ['ergebnis_bis' => (string) $seminar->results_expires_on?->format('d.m.Y')], route('seminars.plan.show', $seminar), 'Seminar ansehen');
    }

    /** Erinnerung an die Seminarleitung kurz vor dem Stichtag für Bericht und Verträge. */
    public function reportDue(Seminar $seminar): void
    {
        $this->toLead($seminar, 'report_due', $this->base($seminar) + ['stichtag' => (string) $seminar->report_due_on?->format('d.m.Y')]);
    }

    /** Stichtag überschritten: Hinweis an die Seminarleitung und an die Koordination. */
    public function reportOverdue(Seminar $seminar): void
    {
        $values = $this->base($seminar) + ['stichtag' => (string) $seminar->report_due_on?->format('d.m.Y')];

        $this->toLead($seminar, 'report_overdue_lead', $values);
        $this->toCapable(Capability::SeminarsPlan, 'seminar_overdue', 'report_overdue_plan', $values, route('seminars.plan.show', $seminar), 'Seminar ansehen');
    }

    /** Neuer Zahlungsauftrag für die Buchhaltung. */
    public function paymentCreated(Seminar $seminar, string $name, string $amount): void
    {
        $this->toCapable(Capability::SeminarsPayments, 'seminar_payments', 'payment', $this->base($seminar) + ['name' => $name, 'betrag' => $amount], route('seminars.payments.index'), 'Zahlungsaufträge ansehen');
    }

    /**
     * Die Werte, die jede Seminarmitteilung kennt.
     *
     * @return array<string, string>
     */
    private function base(Seminar $seminar): array
    {
        return ['nummer' => (string) $seminar->number, 'titel' => (string) $seminar->title, 'schule' => (string) $seminar->partner->name];
    }

    /** @param  array<string, string|list<string>>  $fields */
    private function toLead(Seminar $seminar, string $event, array $fields): void
    {
        $lead = $seminar->lead;

        if ($lead === null || blank($lead->email)) {
            return;
        }

        foreach ($this->preferences->filter([$lead], 'seminar_report_due') as $user) {
            $this->send((string) $user->email, new SeminarNoticeMail($event, $user->person?->fullName(), $fields, route('seminars.lead.show', $seminar), 'Seminar ansehen'));
        }
    }

    /** @param  array<string, string|list<string>>  $fields */
    private function toSchool(Seminar $seminar, string $event, array $fields): void
    {
        if (blank($seminar->contact_email)) {
            return;
        }

        $this->send((string) $seminar->contact_email, new SeminarNoticeMail($event, $seminar->contact_name, $fields, route('school.seminars.show', $seminar), 'Seminar im Portal ansehen'));
    }

    /** Zugesagte Teamende (abschaltbar unter „Hinweise zu Ihren Seminaren“). @param  array<string, string|list<string>>  $fields */
    private function toTeam(Seminar $seminar, string $event, array $fields): void
    {
        $users = SeminarApplication::query()->where('seminar_id', $seminar->id)->whereIn('outcome', ['confirmed', 'waitlist'])->with('user.person')->get()->map(fn (SeminarApplication $application): User => $application->user)->all();

        foreach ($this->preferences->filter($users, 'seminar_team') as $user) {
            $this->send((string) $user->email, new SeminarNoticeMail($event, $user->person?->fullName(), $fields, route('seminars.team.index'), 'Ihre Seminare ansehen'));
        }
    }

    /**
     * An alle Konten, die die Berechtigung haben und die Mitteilung nicht abgeschaltet haben.
     *
     * @param  array<string, string|list<string>>  $fields
     * @param  list<int>  $excludeUserIds
     */
    private function toCapable(Capability $capability, string $preferenceKey, string $event, array $fields, string $url, ?string $urlLabel = null, array $excludeUserIds = []): void
    {
        $keys = Capabilities::roleKeysWith($capability);

        $users = User::query()->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->whereIn('key', $keys)))
            ->with('person')->when($excludeUserIds !== [], fn ($query) => $query->whereNotIn('id', $excludeUserIds))->get();

        foreach ($this->preferences->filter($users, $preferenceKey) as $user) {
            if (filled($user->email)) {
                $this->send((string) $user->email, new SeminarNoticeMail($event, $user->person?->fullName(), $fields, $url, $urlLabel));
            }
        }
    }

    private function send(string $to, SeminarNoticeMail $mail): void
    {
        try {
            Mail::to($to)->send($mail);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}

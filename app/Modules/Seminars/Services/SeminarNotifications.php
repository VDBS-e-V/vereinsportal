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
    public function requested(Seminar $seminar): void
    {
        $this->toSchool($seminar, 'Ihre Seminaranfrage '.$seminar->number, ['Ihre Anfrage für das Seminar „'.$seminar->title.'“ ist bei uns eingegangen. Die Geschäftsstelle prüft sie und meldet sich mit einem Kostenvoranschlag.']);
        $this->toCapable(Capability::SeminarsReview, 'seminar_requests', 'Neue Seminaranfrage '.$seminar->number, ['Die Schule '.$seminar->partner->name.' hat das Seminar „'.$seminar->title.'“ angefragt.'], route('seminars.office.show', $seminar), 'Anfrage ansehen');
    }

    /** Die Schule hat zum Kostenvoranschlag etwas geschrieben (Frage, Annahme, Ablehnung). */
    public function schoolAnswered(Seminar $seminar, string $what): void
    {
        $this->toCapable(Capability::SeminarsReview, 'seminar_requests', 'Antwort zum Kostenvoranschlag '.$seminar->number, ['Die Schule '.$seminar->partner->name.' hat zum Seminar „'.$seminar->title.'“ geantwortet: '.$what.'.'], route('seminars.office.show', $seminar), 'Seminar ansehen');
    }

    /** Anfrage abgelehnt: Begründung an die Schule. */
    public function rejected(Seminar $seminar, ?string $reason): void
    {
        $this->toSchool($seminar, 'Ihre Seminaranfrage '.$seminar->number, array_values(array_filter(['wir können Ihre Anfrage für das Seminar „'.$seminar->title.'“ leider nicht annehmen.', filled($reason) ? 'Begründung: '.$reason : null, 'Bei Fragen melden Sie sich gern bei der Geschäftsstelle.'])));
    }

    /** Seminar freigegeben: die Koordination kann planen. */
    public function released(Seminar $seminar): void
    {
        $this->toCapable(Capability::SeminarsPlan, 'seminar_released', 'Seminar freigegeben: '.$seminar->title, ['Die Geschäftsstelle hat das Seminar „'.$seminar->title.'“ der Schule '.$seminar->partner->name.' freigegeben. Sie können jetzt Termine und Team planen.'], route('seminars.plan.show', $seminar), 'Seminar planen');
    }

    /** Ausschreibung: Hinweis an alle Teamenden, die sich noch nicht gemeldet haben. */
    public function tendered(Seminar $seminar): void
    {
        $applied = $seminar->applications()->pluck('user_id')->all();
        $first = $seminar->sessions()->min('starts_at');

        $this->toCapable(Capability::SeminarsApply, 'seminar_tender', 'Neues Seminar zur Mitarbeit: '.$seminar->title, array_values(array_filter([
            'das Seminar „'.$seminar->title.'“ ('.$seminar->partner->name.') ist für Teamende ausgeschrieben.',
            $first ? 'Erster Termin: '.date('d.m.Y', (int) strtotime((string) $first)).'.' : null,
            'Bewerbung bis '.$seminar->application_deadline?->format('d.m.Y').'.',
        ])), route('seminars.team.index'), 'Interesse melden', $applied);
    }

    /** Erinnerung kurz vor Fristende an Teamende, die sich noch nicht gemeldet haben. */
    public function deadlineNear(Seminar $seminar): void
    {
        $this->toCapable(Capability::SeminarsApply, 'seminar_tender', 'Bewerbungsfrist endet bald: '.$seminar->title, ['die Bewerbungsfrist für das Seminar „'.$seminar->title.'“ endet am '.$seminar->application_deadline?->format('d.m.Y').'. Wenn Sie mitarbeiten möchten, melden Sie Ihr Interesse jetzt.'], route('seminars.team.index'), 'Interesse melden', $seminar->applications()->pluck('user_id')->all());
    }

    /** Seminar abgesagt: Team und Schule erfahren es mit der Begründung. */
    public function cancelled(Seminar $seminar, ?string $reason): void
    {
        $text = 'das Seminar „'.$seminar->title.'“ ('.$seminar->partner->name.') wurde abgesagt.'.(filled($reason) ? ' Begründung: '.$reason : '');

        $this->toSchool($seminar, 'Seminar abgesagt: '.$seminar->title, [$text]);
        $this->toTeam($seminar, 'Seminar abgesagt: '.$seminar->title, [$text]);
    }

    /** Neuer Termin: Team und Schule erfahren es, das Team wird neu angefragt. */
    public function rescheduled(Seminar $seminar, ?string $reason, bool $teamReset): void
    {
        $text = 'für das Seminar „'.$seminar->title.'“ ('.$seminar->partner->name.') wird ein neuer Termin gesucht.'.(filled($reason) ? ' Grund: '.$reason : '');

        $this->toSchool($seminar, 'Neuer Termin für '.$seminar->title, [$text]);

        if ($teamReset) {
            $this->toTeam($seminar, 'Neuer Termin für '.$seminar->title, [$text, 'Das Team wird neu angefragt. Sie erhalten die Ausschreibung, sobald der neue Termin feststeht.']);
        }
    }

    /** Einladung zum Onboarding (Termin, ggf. Link der Videokonferenz) an das bestätigte Team. */
    public function onboardingInvitation(Seminar $seminar): void
    {
        $lines = [
            'zur Vorbereitung des Seminars „'.$seminar->title.'“ laden wir Sie zu einem Onboarding ein.',
            'Termin: '.$seminar->onboarding_at?->format('d.m.Y, H:i').' Uhr.',
        ];

        if (filled($seminar->onboarding_link)) {
            $lines[] = 'Link: '.$seminar->onboarding_link;
        }

        $this->toTeam($seminar, 'Einladung zum Onboarding: '.$seminar->title, $lines);
    }

    /** Briefing an die Schule: Ablauf, Raum, Material. Der Text kommt von der Koordination. */
    public function briefing(Seminar $seminar, string $text): void
    {
        $this->toSchool($seminar, 'Vorbereitung Ihres Seminars '.$seminar->title, [$text]);
    }

    /** Bericht und Ergebnislink an die Schule. */
    public function results(Seminar $seminar): void
    {
        $this->toSchool($seminar, 'Bericht und Ergebnisse zu '.$seminar->title, array_values(array_filter([
            'das Seminar „'.$seminar->title.'“ ist durchgeführt. Den Bericht finden Sie im Portal, die Ergebnisse im Ordner unter diesem Link:',
            (string) $seminar->results_url,
            'Der Link ist bis '.$seminar->results_expires_on?->format('d.m.Y').' erreichbar.',
            'Wir freuen uns über Ihre Rückmeldung zum Seminar. Sie geht im Portal in wenigen Minuten.',
        ])));
    }

    /** Erinnerung an die Seminarleitung kurz vor dem Stichtag für Bericht und Verträge. */
    public function reportDue(Seminar $seminar): void
    {
        $this->toLead($seminar, 'Bericht und Verträge fällig: '.$seminar->title, ['Bericht und Verträge zum Seminar „'.$seminar->title.'“ sind bis '.$seminar->report_due_on?->format('d.m.Y').' fällig. Bitte laden Sie sie im Portal hoch und geben Sie sie ab.']);
    }

    /** Stichtag überschritten: Hinweis an die Seminarleitung und an die Koordination. */
    public function reportOverdue(Seminar $seminar): void
    {
        $this->toLead($seminar, 'Bericht und Verträge überfällig: '.$seminar->title, ['Bericht und Verträge zum Seminar „'.$seminar->title.'“ waren bis '.$seminar->report_due_on?->format('d.m.Y').' fällig. Bitte laden Sie sie im Portal hoch und geben Sie sie ab.']);
        $this->toCapable(Capability::SeminarsPlan, 'seminar_overdue', 'Bericht überfällig: '.$seminar->title, ['Zum Seminar „'.$seminar->title.'“ ('.$seminar->partner->name.') fehlen Bericht und Verträge nach dem Stichtag '.$seminar->report_due_on?->format('d.m.Y').'.'], route('seminars.plan.show', $seminar), 'Seminar ansehen');
    }

    /** Neuer Zahlungsauftrag für die Buchhaltung. */
    public function paymentCreated(Seminar $seminar, string $name, string $amount): void
    {
        $this->toCapable(Capability::SeminarsPayments, 'seminar_payments', 'Neuer Zahlungsauftrag: '.$seminar->number, ['zum Seminar „'.$seminar->title.'“ liegt ein neuer Zahlungsauftrag vor: '.$name.', '.$amount.'.'], route('seminars.payments.index'), 'Zahlungsaufträge ansehen');
    }

    /** @param  list<string>  $paragraphs */
    private function toLead(Seminar $seminar, string $subject, array $paragraphs): void
    {
        $lead = $seminar->lead;

        if ($lead === null || blank($lead->email)) {
            return;
        }

        foreach ($this->preferences->filter([$lead], 'seminar_report_due') as $user) {
            $this->send((string) $user->email, new SeminarNoticeMail($subject, $user->person?->fullName(), $paragraphs, route('seminars.lead.show', $seminar), 'Seminar ansehen'));
        }
    }

    /** @param  list<string>  $paragraphs */
    private function toSchool(Seminar $seminar, string $subject, array $paragraphs): void
    {
        if (blank($seminar->contact_email)) {
            return;
        }

        $this->send((string) $seminar->contact_email, new SeminarNoticeMail($subject, $seminar->contact_name, $paragraphs, route('school.seminars.show', $seminar), 'Seminar im Portal ansehen'));
    }

    /** Zugesagte Teamende (abschaltbar unter „Hinweise zu Ihren Seminaren“). @param  list<string>  $paragraphs */
    private function toTeam(Seminar $seminar, string $subject, array $paragraphs): void
    {
        $users = SeminarApplication::query()->where('seminar_id', $seminar->id)->whereIn('outcome', ['confirmed', 'waitlist'])->with('user.person')->get()->map(fn (SeminarApplication $application): User => $application->user)->all();

        foreach ($this->preferences->filter($users, 'seminar_team') as $user) {
            $this->send((string) $user->email, new SeminarNoticeMail($subject, $user->person?->fullName(), $paragraphs, route('seminars.team.index'), 'Ihre Seminare ansehen'));
        }
    }

    /**
     * An alle Konten, die die Berechtigung haben und die Mitteilung nicht abgeschaltet haben.
     *
     * @param  list<string>  $paragraphs
     * @param  list<int>  $excludeUserIds
     */
    private function toCapable(Capability $capability, string $preferenceKey, string $subject, array $paragraphs, string $url, ?string $urlLabel = null, array $excludeUserIds = []): void
    {
        $keys = Capabilities::roleKeysWith($capability);

        $users = User::query()->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->whereIn('key', $keys)))
            ->with('person')->when($excludeUserIds !== [], fn ($query) => $query->whereNotIn('id', $excludeUserIds))->get();

        foreach ($this->preferences->filter($users, $preferenceKey) as $user) {
            if (filled($user->email)) {
                $this->send((string) $user->email, new SeminarNoticeMail($subject, $user->person?->fullName(), $paragraphs, $url, $urlLabel));
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

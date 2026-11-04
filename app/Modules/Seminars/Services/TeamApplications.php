<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Seminars\Mail\SeminarDeadlineMail;
use App\Modules\Seminars\Mail\SeminarTeamMail;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\TeamProfile;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Teamende bekunden Interesse an terminierten Seminaren mit Bedarf, und zwar bis zu einem Stichtag. Danach sichtet die Koordination die Interessenbekundungen
 * und stellt das Team nach Kriterien zusammen (Kompetenzen, Erfahrung, Alter, Geschlecht, Seminarleitung), bis der Bedarf gedeckt ist.
 * Jede Interessenbekundung und Entscheidung steht im Verlauf des Seminars; das allgemeine Protokoll nennt nur Seminar und Entscheidung, keine Namen.
 */
final class TeamApplications
{
    public function isOpen(Seminar $seminar): bool
    {
        return $seminar->status === SeminarStatus::Scheduled && (int) $seminar->team_needed > 0 && ! $seminar->deadlinePassed();
    }

    public function confirmedCount(Seminar $seminar): int
    {
        return $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->count();
    }

    /** Seminare, für die noch Interesse bekundet werden kann: terminiert, mit Bedarf und vor dem Stichtag (mit Schule, Terminen und Kostenvoranschlag). @return Collection<int, Seminar> */
    public function open(): Collection
    {
        return Seminar::query()
            ->where('status', SeminarStatus::Scheduled->value)->where('team_needed', '>', 0)
            ->with(['partner', 'sessions', 'quotes'])->withCount(['applications as confirmed_count' => fn ($query) => $query->where('status', ApplicationStatus::Confirmed->value)])
            ->get()
            ->reject(fn (Seminar $seminar): bool => $seminar->deadlinePassed())
            ->sortBy(fn (Seminar $seminar): string => (string) $seminar->sessions->first()?->starts_at)
            ->values();
    }

    /** @throws DomainException */
    public function apply(Seminar $seminar, User $user, ?string $note, bool $wantsLead = false): SeminarApplication
    {
        if (! $this->isOpen($seminar)) {
            throw new DomainException($seminar->deadlinePassed() ? 'Der Stichtag ist vorbei. Interesse lässt sich nicht mehr bekunden.' : 'Für dieses Seminar lässt sich kein Interesse bekunden.');
        }

        $existing = $seminar->applications()->where('user_id', $user->id)->first();

        if ($existing instanceof SeminarApplication && $existing->status !== ApplicationStatus::Withdrawn) {
            throw new DomainException($existing->status === ApplicationStatus::Declined ? 'Ihre Interessenbekundung wurde bereits entschieden.' : 'Sie haben schon Interesse bekundet.');
        }

        $application = $existing ?? new SeminarApplication(['seminar_id' => $seminar->id, 'user_id' => $user->id]);
        $application->fill(['status' => ApplicationStatus::Applied, 'note' => filled($note) ? trim((string) $note) : null, 'wants_lead' => $wantsLead, 'decided_by_user_id' => null, 'decided_at' => null])->save();

        $this->record($seminar, SeminarEventType::TeamApplied, $user, $this->name($user));

        return $application;
    }

    /**
     * Bewerbung über den persönlichen Zugang (ohne Konto). Die Angaben gelten für diese Bewerbung; die Adresse kommt aus dem Kontakt.
     *
     * @param  array{name: string, phone?: ?string, birth_year?: ?int, competences?: list<string>, experience?: ?string, note?: ?string, wants_lead?: bool, consent_contact?: bool}  $details
     *
     * @throws DomainException
     */
    public function applyContact(Seminar $seminar, TeamContact $contact, array $details): SeminarApplication
    {
        if (! $this->isOpen($seminar)) {
            throw new DomainException($seminar->deadlinePassed() ? 'Der Stichtag ist vorbei. Interesse lässt sich nicht mehr bekunden.' : 'Für dieses Seminar lässt sich kein Interesse bekunden.');
        }

        $existing = $seminar->applications()->where('contact_id', $contact->id)->first();

        if ($existing instanceof SeminarApplication && $existing->status !== ApplicationStatus::Withdrawn) {
            throw new DomainException($existing->status === ApplicationStatus::Declined ? 'Ihre Interessenbekundung wurde bereits entschieden.' : 'Sie haben schon Interesse bekundet.');
        }

        $application = $existing ?? new SeminarApplication(['seminar_id' => $seminar->id, 'contact_id' => $contact->id]);
        $application->fill([
            'status' => ApplicationStatus::Applied,
            'note' => filled($details['note'] ?? null) ? mb_substr(trim((string) $details['note']), 0, 300) : null,
            'wants_lead' => (bool) ($details['wants_lead'] ?? false),
            'decided_by_user_id' => null,
            'decided_at' => null,
            'guest_name' => mb_substr(trim($details['name']), 0, 150),
            'guest_phone' => filled($details['phone'] ?? null) ? trim((string) $details['phone']) : null,
            'guest_birth_year' => $details['birth_year'] ?? null,
            'guest_competences' => array_values((array) ($details['competences'] ?? [])),
            'guest_experience' => filled($details['experience'] ?? null) ? mb_substr(trim((string) $details['experience']), 0, 800) : null,
            'guest_consent_contact' => (bool) ($details['consent_contact'] ?? false),
        ])->save();

        $this->record($seminar, SeminarEventType::TeamApplied, null, $application->guest_name, 'Persönlicher Zugang');

        return $application;
    }

    /** @throws DomainException */
    public function withdrawContact(SeminarApplication $application): void
    {
        if ($application->status !== ApplicationStatus::Applied) {
            throw new DomainException($application->status === ApplicationStatus::Confirmed ? 'Sie sind bereits im Team. Bitte melden Sie sich bei der Koordination, wenn Sie nicht teilnehmen können.' : 'Diese Interessenbekundung lässt sich nicht zurückziehen.');
        }

        $application->forceFill(['status' => ApplicationStatus::Withdrawn])->save();

        $this->record($application->seminar, SeminarEventType::TeamWithdrawn, null, $application->applicantName(), 'Persönlicher Zugang');
    }

    /** @throws DomainException */
    public function withdraw(SeminarApplication $application, User $user): void
    {
        if ($application->status !== ApplicationStatus::Applied) {
            throw new DomainException($application->status === ApplicationStatus::Confirmed ? 'Sie sind bereits im Team. Bitte melden Sie sich bei der Koordination, wenn Sie nicht teilnehmen können.' : 'Diese Interessenbekundung lässt sich nicht zurückziehen.');
        }

        $application->forceFill(['status' => ApplicationStatus::Withdrawn])->save();

        $this->record($application->seminar, SeminarEventType::TeamWithdrawn, $user, $this->name($user));
    }

    /** @throws DomainException */
    public function decide(SeminarApplication $application, ApplicationStatus $target, User $by): void
    {
        $seminar = $application->seminar;

        if (! in_array($target, [ApplicationStatus::Confirmed, ApplicationStatus::Declined], true)) {
            throw new DomainException('Ungültige Entscheidung.');
        }

        if ($application->status === ApplicationStatus::Withdrawn) {
            throw new DomainException('Die Interessenbekundung wurde zurückgezogen.');
        }

        if ($application->status === $target) {
            return;
        }

        if ($seminar->application_deadline !== null && ! $seminar->deadlinePassed()) {
            throw new DomainException('Der Stichtag ('.$seminar->application_deadline->format('d.m.Y').') ist noch nicht erreicht. Die Koordination sichtet die Interessenbekundungen danach.');
        }

        if ($target === ApplicationStatus::Confirmed && $this->confirmedCount($seminar) >= (int) $seminar->team_needed) {
            throw new DomainException('Der Bedarf an Teamenden ist bereits gedeckt. Erhöhen Sie zuerst den Bedarf.');
        }

        $application->forceFill(['status' => $target, 'decided_by_user_id' => $by->id, 'decided_at' => now()])->save();

        $this->record($seminar, $target === ApplicationStatus::Confirmed ? SeminarEventType::TeamConfirmed : SeminarEventType::TeamDeclined, $by, $application->applicantName());
        AuditLog::record(AuditEvent::SeminarTeamDecided, $by, $seminar, ['entscheidung' => $target->label()]);

        $this->notify($application, $target === ApplicationStatus::Confirmed ? 'confirmed' : 'declined', $seminar);
    }

    /**
     * Bedarf, Stichtag und gesuchte Kompetenzen setzen (Bedarf 0 oder leer = kein Interesse mehr möglich).
     *
     * @param  list<string>  $competences  Schlüssel aus portal.team.competences
     */
    public function setNeed(Seminar $seminar, ?int $needed, ?Carbon $deadline, array $competences, User $by): void
    {
        $needed = $needed !== null && $needed > 0 ? $needed : null;
        $known = array_keys((array) config('portal.team.competences', []));
        $competences = array_values(array_intersect($known, $competences));

        $seminar->fill(['team_needed' => $needed, 'application_deadline' => $deadline?->startOfDay(), 'team_competences' => $competences === [] ? null : $competences, 'updated_by_user_id' => $by->id]);

        // Ein neuer Stichtag in der Zukunft öffnet die Erinnerung an die Koordination erneut.
        if ($seminar->isDirty('application_deadline')) {
            $seminar->deadline_notified_at = null;
        }

        if (! $seminar->isDirty(['team_needed', 'application_deadline', 'team_competences'])) {
            return;
        }

        $seminar->save();

        $this->record($seminar, SeminarEventType::TeamNeedChanged, $by, $needed === null ? 'kein Bedarf' : $needed.' Teamende, Stichtag '.($deadline?->format('d.m.Y') ?? 'offen'));
    }

    /** Interne Notiz der Koordination zu einer Interessenbekundung (nur für die Koordination sichtbar). */
    public function note(SeminarApplication $application, ?string $note): void
    {
        $application->forceFill(['internal_note' => filled($note) ? trim((string) $note) : null])->save();
    }

    /**
     * Daten zum Sichten der Interessenbekundungen: je Person Alter, Angaben aus dem Profil und Erfahrung im Portal (Seminare, in denen sie schon im Team waren und die durchgeführt sind),
     * dazu eine Übersicht über das bisher zusammengestellte Team (Geschlechter, Durchschnittsalter, Kompetenzen, Seminarleitung).
     *
     * @return array{rows: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function review(Seminar $seminar): array
    {
        $applications = $seminar->applications->reject(fn (SeminarApplication $application): bool => $application->status === ApplicationStatus::Withdrawn)->values();
        $userIds = $applications->pluck('user_id')->filter()->all();
        $contactIds = $applications->pluck('contact_id')->filter()->all();

        $contactExperience = SeminarApplication::query()->whereIn('contact_id', $contactIds)->where('status', ApplicationStatus::Confirmed->value)
            ->whereHas('seminar', fn ($query) => $query->whereIn('status', [SeminarStatus::Done->value, SeminarStatus::Completed->value]))
            ->selectRaw('contact_id, count(*) as aggregate')->groupBy('contact_id')->pluck('aggregate', 'contact_id');

        $profiles = TeamProfile::query()->whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $experience = SeminarApplication::query()->whereIn('user_id', $userIds)->where('status', ApplicationStatus::Confirmed->value)
            ->whereHas('seminar', fn ($query) => $query->whereIn('status', [SeminarStatus::Done->value, SeminarStatus::Completed->value]))
            ->selectRaw('user_id, count(*) as aggregate')->groupBy('user_id')->pluck('aggregate', 'user_id');

        $rows = $applications->map(function (SeminarApplication $application) use ($profiles, $experience, $contactExperience, $seminar): array {
            if ($application->isContact()) {
                return [
                    'application' => $application,
                    'name' => $application->applicantName(),
                    'age' => $application->guest_birth_year ? max(0, (int) now()->format('Y') - (int) $application->guest_birth_year) : null,
                    'gender' => null,
                    'competences' => (array) ($application->guest_competences ?? []),
                    'experience_note' => $application->guest_experience,
                    'seminars_done' => (int) ($contactExperience[$application->contact_id] ?? 0),
                    'lead' => $application->wants_lead,
                    'lead_experience' => false,
                    'conflicts' => $this->conflictsFor($application, $seminar)->pluck('title')->all(),
                    'via_link' => true,
                ];
            }

            $user = $application->user;
            $profile = $profiles->get($user->id);
            $birth = $user->person?->birth_date;

            return [
                'application' => $application,
                'name' => $user->person?->fullName() ?: $user->email,
                'age' => $birth?->age,
                'gender' => $profile?->gender,
                'competences' => (array) ($profile?->competences ?? []),
                'experience_note' => $profile?->experience,
                'seminars_done' => (int) ($experience[$user->id] ?? 0),
                'lead' => $application->wants_lead,
                'lead_experience' => (bool) $profile?->lead_experience,
                'conflicts' => $this->conflicts($user, $seminar)->pluck('title')->all(),
            ];
        })->all();

        $team = collect($rows)->filter(fn (array $row): bool => $row['application']->status === ApplicationStatus::Confirmed);
        $wanted = (array) ($seminar->team_competences ?? []);
        $covered = $team->flatMap(fn (array $row): array => $row['competences'])->unique()->all();
        $ages = $team->pluck('age')->filter(fn ($age): bool => $age !== null);

        return ['rows' => $rows, 'summary' => [
            'size' => $team->count(),
            'needed' => (int) $seminar->team_needed,
            'genders' => $team->groupBy(fn (array $row): string => $row['gender']?->label() ?? 'keine Angabe')->map->count()->all(),
            'average_age' => $ages->isEmpty() ? null : (int) round($ages->avg()),
            'wanted' => $wanted,
            'covered' => array_values(array_intersect($wanted, $covered)),
            'missing' => array_values(array_diff($wanted, $covered)),
            'leaders' => $team->filter(fn (array $row): bool => $row['lead'] || $row['lead_experience'])->count(),
        ]];
    }

    /**
     * Teamvorschlag für die offenen Plätze, Schritt für Schritt: Gewählt wird jeweils, wer am meisten zum Team beiträgt, nämlich noch nicht abgedeckte gesuchte Kompetenzen,
     * Seminarleitung (solange das Team noch keine hat) und Erfahrung aus durchgeführten Seminaren. Wer sich zeitlich mit einem bestätigten Seminar überschneidet, wird nicht vorgeschlagen.
     * Alter und Geschlecht fließen bewusst nicht ein; die Koordination entscheidet und kann die Auswahl jederzeit ändern.
     *
     * @return array{picks: list<array{application: SeminarApplication, name: string, reasons: list<string>}>, slots: int, conflicts: int}
     */
    public function suggest(Seminar $seminar): array
    {
        $rows = collect($this->review($seminar)['rows']);
        $team = $rows->filter(fn (array $row): bool => $row['application']->status === ApplicationStatus::Confirmed);
        $slots = max(0, (int) $seminar->team_needed - $team->count());
        $pool = $rows->filter(fn (array $row): bool => $row['application']->status === ApplicationStatus::Applied);
        $candidates = $pool->filter(fn (array $row): bool => $row['conflicts'] === [])->values();
        $labels = (array) config('portal.team.competences', []);
        $wanted = (array) ($seminar->team_competences ?? []);
        $covered = $team->flatMap(fn (array $row): array => $row['competences'])->unique()->all();
        $hasLead = $team->contains(fn (array $row): bool => $row['lead'] || $row['lead_experience']);
        $picks = [];

        while (count($picks) < $slots && $candidates->isNotEmpty()) {
            $best = null;
            $bestScore = -1.0;
            $bestReasons = [];

            foreach ($candidates as $row) {
                $score = 0.0;
                $reasons = [];
                $matching = array_values(array_intersect($row['competences'], $wanted));
                $new = array_values(array_diff($matching, $covered));

                if ($new !== []) {
                    $score += 3 * count($new);
                    $reasons[] = 'deckt gesuchte Kompetenz ab: '.implode(', ', array_map(fn (string $key): string => $labels[$key] ?? $key, $new));
                } elseif ($matching !== []) {
                    $score += 1;
                    $reasons[] = 'bringt gesuchte Kompetenz mit';
                }

                if (! $hasLead && ($row['lead'] || $row['lead_experience'])) {
                    $score += 4;
                    $reasons[] = $row['lead_experience'] ? 'kann die Seminarleitung übernehmen (Erfahrung)' : 'würde die Seminarleitung übernehmen';
                }

                if ($row['seminars_done'] > 0) {
                    $score += min($row['seminars_done'], 5) * 0.5;
                    $reasons[] = $row['seminars_done'].' Seminar(e) Erfahrung im Portal';
                }

                if ($score > $bestScore) {
                    $best = $row;
                    $bestScore = $score;
                    $bestReasons = $reasons;
                }
            }

            $picks[] = ['application' => $best['application'], 'name' => $best['name'], 'reasons' => $bestReasons === [] ? ['früheste Interessenbekundung'] : $bestReasons];
            $covered = array_values(array_unique(array_merge($covered, $best['competences'])));
            $hasLead = $hasLead || $best['lead'] || $best['lead_experience'];
            $candidates = $candidates->reject(fn (array $row): bool => $row['application']->id === $best['application']->id)->values();
        }

        return ['picks' => $picks, 'slots' => $slots, 'conflicts' => $pool->count() - $pool->filter(fn (array $row): bool => $row['conflicts'] === [])->count()];
    }

    /**
     * Übernimmt den aktuellen Teamvorschlag (berechnet ihn neu). Gibt die Zahl der Aufgenommenen zurück.
     *
     * @throws DomainException wenn der Stichtag noch nicht erreicht ist
     */
    public function applySuggestion(Seminar $seminar, User $by): int
    {
        $count = 0;

        foreach ($this->suggest($seminar)['picks'] as $pick) {
            $this->decide($pick['application'], ApplicationStatus::Confirmed, $by);
            $count++;
        }

        return $count;
    }

    /** Seminare, deren Stichtag vorbei ist und für die die Koordination noch nicht informiert wurde, werden gemeldet. Gibt die Zahl der gemeldeten Seminare zurück. */
    public function notifyDeadlines(): int
    {
        $count = 0;

        $seminars = Seminar::query()->where('status', SeminarStatus::Scheduled->value)->where('team_needed', '>', 0)->whereNotNull('application_deadline')->whereNull('deadline_notified_at')
            ->withCount(['applications as interested_count' => fn ($query) => $query->where('status', ApplicationStatus::Applied->value)])->get()
            ->filter(fn (Seminar $seminar): bool => $seminar->deadlinePassed());

        if ($seminars->isEmpty()) {
            return 0;
        }

        $recipients = User::query()->whereIn('id', RoleAssignment::query()
            ->where('starts_at', '<=', now())->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('role', fn ($query) => $query->where('key', RoleKey::Coordination->value))->select('user_id'))->get();
        $recipients = app(\App\Modules\Access\Services\NotificationPreferences::class)->filter($recipients, 'seminar_deadline');

        foreach ($seminars as $seminar) {
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->send(new SeminarDeadlineMail($seminar, (int) $seminar->interested_count, route('seminars.show', $seminar)));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            $seminar->forceFill(['deadline_notified_at' => now()])->save();
            $count++;
        }

        return $count;
    }

    /** Wie conflicts(), für Konto oder Kontakt einer Bewerbung. @return Collection<int, Seminar> */
    public function conflictsFor(SeminarApplication $application, Seminar $seminar): Collection
    {
        return $application->isContact() ? $this->overlaps('contact_id', (int) $application->contact_id, $seminar) : $this->conflicts($application->user, $seminar);
    }

    /** Bestätigte Seminare der Person, die sich zeitlich mit diesem überschneiden. @return Collection<int, Seminar> */
    public function conflicts(User $user, Seminar $seminar): Collection
    {
        return $this->overlaps('user_id', $user->id, $seminar);
    }

    /** @return Collection<int, Seminar> */
    private function overlaps(string $column, int $id, Seminar $seminar): Collection
    {
        $mine = $seminar->relationLoaded('sessions') ? $seminar->sessions : $seminar->sessions()->get();

        return Seminar::query()
            ->whereKeyNot($seminar->id)
            ->whereIn('id', SeminarApplication::query()->where($column, $id)->where('status', ApplicationStatus::Confirmed->value)->select('seminar_id'))
            ->whereNotIn('status', [SeminarStatus::Cancelled->value])
            ->with('sessions')
            ->get()
            ->filter(fn (Seminar $other): bool => $other->sessions->contains(fn ($theirs): bool => $mine->contains(fn ($session): bool => $session->starts_at < $theirs->ends_at && $theirs->starts_at < $session->ends_at)))
            ->values();
    }

    /** Wird ein Seminar abgesagt, erfahren es alle Bestätigten und Beworbenen. */
    public function notifyCancelled(Seminar $seminar): void
    {
        $seminar->applications()->whereIn('status', [ApplicationStatus::Applied->value, ApplicationStatus::Confirmed->value])->with(['user', 'contact'])->get()
            ->each(fn (SeminarApplication $application) => $this->notify($application, 'cancelled', $seminar));
    }

    /** Termine der bestätigten Seminare als iCalendar-Datei. */
    public function calendar(User $user): string
    {
        $seminars = Seminar::query()
            ->whereIn('id', SeminarApplication::query()->where('user_id', $user->id)->where('status', ApplicationStatus::Confirmed->value)->select('seminar_id'))
            ->whereNotIn('status', [SeminarStatus::Cancelled->value])
            ->with(['partner', 'sessions'])->get();

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'vdbs.portal';
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//VDBS//Vereinsportal//DE', 'CALSCALE:GREGORIAN', 'X-WR-CALNAME:VDBS Seminare'];

        foreach ($seminars as $seminar) {
            foreach ($seminar->sessions as $session) {
                $place = $session->location ?: trim($seminar->partner->name.', '.trim(($seminar->partner->street ?? '').' '.($seminar->partner->house_number ?? '').', '.($seminar->partner->postal_code ?? '').' '.($seminar->partner->city ?? ''), ' ,'));

                array_push($lines,
                    'BEGIN:VEVENT',
                    "UID:seminar-{$seminar->id}-session-{$session->id}@{$host}",
                    'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
                    'DTSTART:'.$session->starts_at->copy()->utc()->format('Ymd\THis\Z'),
                    'DTEND:'.$session->ends_at->copy()->utc()->format('Ymd\THis\Z'),
                    'SUMMARY:'.$this->escape($seminar->title),
                    'LOCATION:'.$this->escape($place),
                    'DESCRIPTION:'.$this->escape($seminar->type->label().($session->notes ? ' – '.$session->notes : '')),
                    'END:VEVENT',
                );
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private function escape(string $text): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    private function name(User $user): string
    {
        return $user->person?->fullName() ?: $user->email;
    }

    /** Nachricht an Konto oder (über den persönlichen Zugang) an den Kontakt. Gesperrte und abgemeldete Adressen bekommen nichts. */
    private function notify(SeminarApplication $application, string $kind, Seminar $seminar): void
    {
        if (! $application->isContact()) {
            $this->send($application->user, $kind, $seminar);

            return;
        }

        $contact = $application->contact;

        if ($contact === null || ! app(\App\Modules\Contacts\Services\ContactAccessSender::class)->canMail($contact)) {
            return;
        }

        try {
            Mail::to($contact->email)->send(new SeminarTeamMail($kind, $seminar->loadMissing(['partner', 'sessions']), route('access.code')));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function send(User $user, string $kind, Seminar $seminar): void
    {
        if (! app(\App\Modules\Access\Services\NotificationPreferences::class)->allows($user, 'seminar_team')) {
            return;
        }

        try {
            Mail::to($user->email)->send(new SeminarTeamMail($kind, $seminar->loadMissing(['partner', 'sessions']), route('seminar-applications.index')));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function record(Seminar $seminar, SeminarEventType $type, ?User $by, ?string $comment = null, ?string $actorLabel = null): void
    {
        $seminar->events()->create([
            'type' => $type,
            'occurred_at' => now(),
            'actor_user_id' => $by?->id,
            'actor_label' => $by !== null ? AuditLog::label($by) : $actorLabel,
            'comment' => $comment,
        ]);
    }
}

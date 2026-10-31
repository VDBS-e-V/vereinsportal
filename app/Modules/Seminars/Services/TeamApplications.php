<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarTeamMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Teamende bewerben sich auf terminierte Seminare mit Bedarf, die Koordination bestätigt oder lehnt ab, bis der Bedarf gedeckt ist.
 * Jede Bewerbung und Entscheidung steht im Verlauf des Seminars; das allgemeine Protokoll nennt nur Seminar und Entscheidung, keine Namen.
 */
final class TeamApplications
{
    public function isOpen(Seminar $seminar): bool
    {
        return $seminar->status === SeminarStatus::Scheduled && (int) $seminar->team_needed > 0;
    }

    public function confirmedCount(Seminar $seminar): int
    {
        return $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->count();
    }

    /** Offene Seminare, bei denen der Bedarf noch nicht gedeckt ist (mit Schule, Terminen und Kostenvoranschlag). @return Collection<int, Seminar> */
    public function open(): Collection
    {
        return Seminar::query()
            ->where('status', SeminarStatus::Scheduled->value)->where('team_needed', '>', 0)
            ->with(['partner', 'sessions', 'quotes'])->withCount(['applications as confirmed_count' => fn ($query) => $query->where('status', ApplicationStatus::Confirmed->value)])
            ->get()
            ->filter(fn (Seminar $seminar): bool => $seminar->confirmed_count < $seminar->team_needed)
            ->sortBy(fn (Seminar $seminar): string => (string) $seminar->sessions->first()?->starts_at)
            ->values();
    }

    /** @throws DomainException */
    public function apply(Seminar $seminar, User $user, ?string $note): SeminarApplication
    {
        if (! $this->isOpen($seminar) || $this->confirmedCount($seminar) >= (int) $seminar->team_needed) {
            throw new DomainException('Für dieses Seminar sind keine Bewerbungen mehr möglich.');
        }

        $existing = $seminar->applications()->where('user_id', $user->id)->first();

        if ($existing instanceof SeminarApplication && $existing->status !== ApplicationStatus::Withdrawn) {
            throw new DomainException($existing->status === ApplicationStatus::Declined ? 'Ihre Bewerbung wurde bereits entschieden.' : 'Sie haben sich schon beworben.');
        }

        $application = $existing ?? new SeminarApplication(['seminar_id' => $seminar->id, 'user_id' => $user->id]);
        $application->fill(['status' => ApplicationStatus::Applied, 'note' => filled($note) ? trim((string) $note) : null, 'decided_by_user_id' => null, 'decided_at' => null])->save();

        $this->record($seminar, SeminarEventType::TeamApplied, $user, $this->name($user));

        return $application;
    }

    /** @throws DomainException */
    public function withdraw(SeminarApplication $application, User $user): void
    {
        if ($application->status !== ApplicationStatus::Applied) {
            throw new DomainException($application->status === ApplicationStatus::Confirmed ? 'Sie sind bereits bestätigt. Bitte melden Sie sich bei der Koordination, wenn Sie nicht teilnehmen können.' : 'Diese Bewerbung lässt sich nicht zurückziehen.');
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
            throw new DomainException('Die Bewerbung wurde zurückgezogen.');
        }

        if ($application->status === $target) {
            return;
        }

        if ($target === ApplicationStatus::Confirmed && $this->confirmedCount($seminar) >= (int) $seminar->team_needed) {
            throw new DomainException('Der Bedarf an Teamenden ist bereits gedeckt. Erhöhen Sie zuerst den Bedarf.');
        }

        $application->forceFill(['status' => $target, 'decided_by_user_id' => $by->id, 'decided_at' => now()])->save();

        $user = $application->user;
        $this->record($seminar, $target === ApplicationStatus::Confirmed ? SeminarEventType::TeamConfirmed : SeminarEventType::TeamDeclined, $by, $this->name($user));
        AuditLog::record(AuditEvent::SeminarTeamDecided, $by, $seminar, ['entscheidung' => $target->label()]);

        $this->send($user, $target === ApplicationStatus::Confirmed ? 'confirmed' : 'declined', $seminar);
    }

    /** Bedarf setzen (0 oder leer = keine Bewerbungen). */
    public function setNeed(Seminar $seminar, ?int $needed, User $by): void
    {
        $needed = $needed !== null && $needed > 0 ? $needed : null;

        if ($needed === $seminar->team_needed) {
            return;
        }

        $seminar->forceFill(['team_needed' => $needed, 'updated_by_user_id' => $by->id])->save();

        $this->record($seminar, SeminarEventType::TeamNeedChanged, $by, $needed === null ? 'kein Bedarf' : $needed.' Teamende');
    }

    /** Bestätigte Seminare der Person, die sich zeitlich mit diesem überschneiden. @return Collection<int, Seminar> */
    public function conflicts(User $user, Seminar $seminar): Collection
    {
        $mine = $seminar->relationLoaded('sessions') ? $seminar->sessions : $seminar->sessions()->get();

        return Seminar::query()
            ->whereKeyNot($seminar->id)
            ->whereIn('id', SeminarApplication::query()->where('user_id', $user->id)->where('status', ApplicationStatus::Confirmed->value)->select('seminar_id'))
            ->whereNotIn('status', [SeminarStatus::Cancelled->value])
            ->with('sessions')
            ->get()
            ->filter(fn (Seminar $other): bool => $other->sessions->contains(fn ($theirs): bool => $mine->contains(fn ($session): bool => $session->starts_at < $theirs->ends_at && $theirs->starts_at < $session->ends_at)))
            ->values();
    }

    /** Wird ein Seminar abgesagt, erfahren es alle Bestätigten und Beworbenen. */
    public function notifyCancelled(Seminar $seminar): void
    {
        $seminar->applications()->whereIn('status', [ApplicationStatus::Applied->value, ApplicationStatus::Confirmed->value])->with('user')->get()
            ->each(fn (SeminarApplication $application) => $this->send($application->user, 'cancelled', $seminar));
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

    private function send(User $user, string $kind, Seminar $seminar): void
    {
        try {
            Mail::to($user->email)->send(new SeminarTeamMail($kind, $seminar->loadMissing(['partner', 'sessions']), route('seminar-applications.index')));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function record(Seminar $seminar, SeminarEventType $type, ?User $by, ?string $comment = null): void
    {
        $seminar->events()->create([
            'type' => $type,
            'occurred_at' => now(),
            'actor_user_id' => $by?->id,
            'actor_label' => $by !== null ? AuditLog::label($by) : null,
            'comment' => $comment,
        ]);
    }
}

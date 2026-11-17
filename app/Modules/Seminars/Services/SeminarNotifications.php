<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Contacts\Services\ContactAccessSender;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Support\CoordinationRecipients;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Mitteilungen im Seminarablauf (docs/87, Phase 4): ein Dienst für alle Empfängerkreise. Zu- und Absagen an das Team und der KVA an die Schule sind Pflicht-Mails;
 * alles andere folgt den Einstellungen im Konto. Im Verlauf des Seminars steht, was an wie viele Empfänger ging (ohne Text und Adressen).
 */
final class SeminarNotifications
{
    /** Reagiert auf einen Statuswechsel (vom SeminarWorkflow aufgerufen). */
    public function statusChanged(Seminar $seminar, SeminarStatus $to, ?string $comment): void
    {
        match ($to) {
            SeminarStatus::QuoteSent => $this->toSchool($seminar, 'school_quote', null, 'KVA an die Schule'),
            SeminarStatus::Rejected => $this->toSchool($seminar, 'school_rejected', $comment, 'Ablehnung an die Schule'),
            SeminarStatus::Cancelled => $this->cancelled($seminar, $comment),
            SeminarStatus::Released => $this->toCoordination($seminar, 'released', 'Freigabe an die Koordination'),
            SeminarStatus::Tendered => $this->toTender($seminar),
            default => null,
        };
    }

    /** Eingangsbestätigung an die anfragende Person. */
    public function requestReceived(Seminar $seminar, User $requester): void
    {
        $this->deliver([$requester->email], 'school_received', $seminar, route('school.home'), null, 'Eingangsbestätigung an die Schule');
    }

    /** Rückfrage der Geschäftsstelle an die Schule (der Text steht in der Mail und im Verlauf). */
    public function question(Seminar $seminar, string $text, User $by): void
    {
        $this->toSchool($seminar, 'school_question', trim($text), 'Rückfrage an die Schule', $by, $text);
    }

    /** Briefing: Ablauf und Hinweise an die Schule. */
    public function briefing(Seminar $seminar, ?string $text, User $by): void
    {
        $seminar->forceFill(['briefing_sent_at' => now()])->save();
        $this->toSchool($seminar, 'school_briefing', $text, 'Briefing an die Schule', $by);
    }

    /** Neue Termine: Team und Schule. */
    public function rescheduled(Seminar $seminar, User $by): void
    {
        $this->toSchool($seminar, 'school_rescheduled', null, 'Neue Termine an die Schule', $by);
        $this->toTeam($seminar, 'team_rescheduled', null, 'Neue Termine an das Team', $by);
    }

    /** Bericht und Ergebnislink an die Schule, mit Bitte um Rückmeldung. */
    public function reportToSchool(Seminar $seminar, ?string $text, User $by): void
    {
        $this->toSchool($seminar, 'school_report', $text, 'Bericht und Ergebnislink an die Schule', $by);
    }

    /** Erinnerung an die Seminarleitung (Stichtag für Verträge und Bericht naht). */
    public function reportReminder(Seminar $seminar, SeminarApplication $lead): void
    {
        $this->toApplications($seminar, [$lead], 'report_reminder', null, 'Erinnerung an die Seminarleitung');
    }

    /** Hinweis an die Koordination: Der Stichtag für die Berichte ist verstrichen. */
    public function reportOverdue(Seminar $seminar): void
    {
        $this->toCoordination($seminar, 'report_overdue', 'Bericht überfällig an die Koordination');
    }

    /** Onboarding-Einladung an das bestätigte Team. */
    public function onboarding(Seminar $seminar, ?string $text, User $by): void
    {
        $this->toTeam($seminar, 'onboarding', $text, 'Onboarding-Einladung an das Team', $by);
    }

    /** Mail an die Person, die die Seminarleitung übernimmt. */
    public function leadAssigned(Seminar $seminar, SeminarApplication $application): void
    {
        $this->toApplications($seminar, [$application], 'lead', null, 'Hinweis an die Seminarleitung');
    }

    private function cancelled(Seminar $seminar, ?string $comment): void
    {
        $this->toSchool($seminar, 'school_cancelled', $comment, 'Absage an die Schule');
    }

    private function toSchool(Seminar $seminar, string $kind, ?string $extra, string $label, ?User $by = null, ?string $eventText = null): void
    {
        $this->deliver($this->schoolAddresses($seminar), $kind, $seminar, route('school.home'), $extra, $label, $by, $eventText);
    }

    private function toCoordination(Seminar $seminar, string $kind, string $label): void
    {
        $users = app(NotificationPreferences::class)->filter(CoordinationRecipients::users(), 'seminar_workflow');
        $this->deliver(array_map(fn (User $user): string => $user->email, $users), $kind, $seminar, route('seminars.show', $seminar), null, $label);
    }

    /** Ausschreibung an alle Teamenden, die Ausschreibungen bekommen wollen. */
    private function toTender(Seminar $seminar): void
    {
        $users = User::query()->whereIn('id', RoleAssignment::query()
            ->where('starts_at', '<=', now())->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('role', fn ($query) => $query->where('key', RoleKey::Team->value))->select('user_id'))->limit(1000)->get();

        $addresses = array_map(fn (User $user): string => $user->email, app(NotificationPreferences::class)->filter($users, 'seminar_tender'));
        $this->deliver($addresses, 'tender', $seminar, route('seminar-applications.index'), null, 'Ausschreibung an Teamende');
    }

    private function toTeam(Seminar $seminar, string $kind, ?string $extra, string $label, ?User $by = null): void
    {
        $applications = $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->with(['user', 'contact'])->get()->all();
        $this->toApplications($seminar, $applications, $kind, $extra, $label, $by);
    }

    /** @param  list<SeminarApplication>  $applications */
    private function toApplications(Seminar $seminar, array $applications, string $kind, ?string $extra, string $label, ?User $by = null): void
    {
        $addresses = [];
        $prefs = app(NotificationPreferences::class);

        foreach ($applications as $application) {
            if (! $application->isContact()) {
                if ($application->user !== null && $prefs->allows($application->user, 'seminar_team')) {
                    $addresses[] = $application->user->email;
                }

                continue;
            }

            if ($application->contact !== null && app(ContactAccessSender::class)->canMail($application->contact)) {
                $addresses[] = $application->contact->email;
            }
        }

        $this->deliver($addresses, $kind, $seminar, route('seminar-applications.index'), $extra, $label, $by);
    }

    /** @return list<string> */
    private function schoolAddresses(Seminar $seminar): array
    {
        $seminar->loadMissing('partner');
        $addresses = PartnerUser::query()->where('partner_id', $seminar->partner_id)->with('user')->get()->map(fn (PartnerUser $account): ?string => $account->user?->email)->filter()->all();

        if ($addresses === [] && filled($seminar->partner?->email)) {
            $addresses = [(string) $seminar->partner->email];
        }

        return array_values(array_unique($addresses));
    }

    /** @param  list<string>  $addresses */
    private function deliver(array $addresses, string $kind, Seminar $seminar, string $url, ?string $extra, string $label, ?User $by = null, ?string $eventText = null): void
    {
        $addresses = array_values(array_unique(array_filter($addresses)));

        if ($addresses === []) {
            return;
        }

        $seminar->loadMissing(['partner', 'sessions']);
        $sent = 0;

        foreach ($addresses as $address) {
            try {
                Mail::to($address)->send(new SeminarNoticeMail($kind, $seminar, $url, $extra));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $seminar->events()->create([
            'type' => SeminarEventType::Note,
            'occurred_at' => now(),
            'actor_user_id' => $by?->id,
            'actor_label' => $by !== null ? AuditLog::label($by) : 'System',
            'comment' => 'Mail gesendet: '.$label.' ('.$sent.' Empfänger'.($eventText !== null ? '). Text: '.mb_substr($eventText, 0, 300) : ').'),
        ]);
    }
}

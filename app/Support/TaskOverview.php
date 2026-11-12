<?php

namespace App\Support;

use App\Modules\Access\Models\User;
use App\Modules\AccessRequests\Enums\AccessRequestStatus;
use App\Modules\AccessRequests\Models\AccessRequest;
use App\Modules\Contracts\Models\SeminarContract;
use App\Modules\Forms\Models\FormApplication;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\TeamProfile;
use App\Modules\Seminars\Services\TeamApplications;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Services\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * „Ihre Aufgaben“ im Konto und in den Bereichen (nicht auf der öffentlichen Startseite): Was für die angemeldete Person gerade ansteht, nach Berechtigungen. Jeder Eintrag ist ein Hinweis mit Zahl und Link,
 * es werden nur Zahlen gezeigt, keine Namen oder Inhalte. Einträge ohne etwas zu tun entfallen.
 */
final class TaskOverview
{
    /** @return list<array{label: string, count: ?int, href: string, tone: string}> */
    public static function for(User $user): array
    {
        $can = fn (string $ability): bool => Gate::forUser($user)->allows($ability);
        $tasks = [];
        $add = function (string $label, ?int $count, string $href, string $tone = 'warning') use (&$tasks): void {
            if ($count === null || $count > 0) {
                $tasks[] = ['label' => $label, 'count' => $count, 'href' => $href, 'tone' => $tone];
            }
        };

        // Teamende
        if ($can('team.onboarding')) {
            $onboarding = TeamOnboarding::query()->where('user_id', $user->id)->whereNull('completed_at')->with('steps')->first();

            if ($onboarding !== null) {
                [$done, $all] = $onboarding->progress();
                $add('Schritte Ihrer Einarbeitung sind offen', $all - $done, route('team-self.index'));
            }
        }

        if ($can('seminars.apply')) {
            if (TeamProfile::query()->where('user_id', $user->id)->first()?->isEmpty() ?? true) {
                $add('Profil für die Teamzusammenstellung ergänzen', null, route('team-profile.edit'), 'info');
            }

            $mine = SeminarApplication::query()->where('user_id', $user->id)->whereIn('status', [ApplicationStatus::Applied->value, ApplicationStatus::Confirmed->value])->pluck('seminar_id');
            $add('Seminare mit offenem Stichtag, für die Sie Interesse bekunden können', app(TeamApplications::class)->open()->reject(fn (Seminar $seminar): bool => $mine->contains($seminar->id))->count(), route('seminar-applications.index'));

            $upcoming = Seminar::query()->whereIn('id', SeminarApplication::query()->where('user_id', $user->id)->where('status', ApplicationStatus::Confirmed->value)->select('seminar_id'))
                ->whereIn('status', [SeminarStatus::Scheduled->value, SeminarStatus::Confirmed->value])->whereHas('sessions', fn ($query) => $query->where('starts_at', '>', now()))->count();
            $add('Bevorstehende Seminare, bei denen Sie im Team sind', $upcoming, route('seminar-applications.index'), 'success');
        }

        // Schulen
        if ($can('school.area')) {
            $partnerId = PartnerUser::query()->where('user_id', $user->id)->value('partner_id');
            $waiting = $partnerId === null ? 0 : Seminar::query()->where('partner_id', $partnerId)->where('status', SeminarStatus::Scheduled->value)->whereHas('quotes')
                ->whereDoesntHave('events', fn ($query) => $query->where('type', SeminarEventType::SchoolConfirmed->value))->count();
            $add('Seminare warten auf Ihre Bestätigung von Termin und Kostenvoranschlag', $waiting, route('school.home'));
        }

        // Koordination
        if ($can('seminars.manage')) {
            $waiting = Seminar::query()->where('status', SeminarStatus::Scheduled->value)->where('team_needed', '>', 0)->whereNotNull('application_deadline')->whereDate('application_deadline', '<', now()->toDateString())
                ->whereHas('applications', fn ($query) => $query->where('status', ApplicationStatus::Applied->value))->count();
            $add('Seminare, deren Team nach dem Stichtag noch zusammengestellt werden muss', $waiting, route('seminars.index'));
            $add('Neue Seminaranfragen', Seminar::query()->where('status', SeminarStatus::Request->value)->count(), route('seminars.index', ['status' => 'request']), 'info');
        }

        if ($can('team.view')) {
            $add('Teamende mit offener Einarbeitung', TeamOnboarding::query()->whereNull('completed_at')->count(), route('team-onboarding.index'), 'info');
        }

        // Geschäftsstelle
        if ($can('access_requests.view')) {
            $add('Neue Zugangsanträge', AccessRequest::query()->where('status', AccessRequestStatus::New->value)->count(), route('access-requests.index'));
        }

        // Vorstand
        if ($can('memberships.view')) {
            $add('Mitgliedsanträge warten auf eine Entscheidung', Membership::query()->where('status', MembershipStatus::Requested->value)->count(), route('membership.index'));
        }

        // Anträge (nur Zahlen) und Verträge
        if ($can('forms.manage')) {
            $add('Anträge warten auf Prüfung', FormApplication::query()->whereIn('status', ['submitted', 'in_review'])->count(), route('forms-office.index'));
        }

        if ($can('forms.approve')) {
            $add('Anträge warten auf Freigabe durch den Vorstand', FormApplication::query()->where('status', 'in_review')->where('approval_level', 'board')->count(), route('forms-office.index'));
        }

        if ($can('contracts.manage')) {
            $missing = SeminarContract::query()->where('status', 'created')
                ->whereHas('seminar.sessions', fn ($query) => $query->where('ends_at', '<', now()))
                ->whereDoesntHave('seminar.sessions', fn ($query) => $query->where('ends_at', '>=', now()))->count();
            $add('Verträge warten auf den unterschriebenen Scan', $missing, route('seminars.index'));
        }

        // Aufgabensystem: eigene offene und überfällige Aufgaben aus den Projekten, die die Person sehen darf
        if ($can('tasks.use')) {
            $mine = Task::query()->whereNull('done_at')
                ->whereIn('task_project_id', app(ProjectAccess::class)->visible($user)->select('task_projects.id'))
                ->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', $user->id));
            $overdue = (clone $mine)->whereDate('due_on', '<', today())->count();

            $add('Überfällige Aufgaben im Aufgabensystem', $overdue, route('tasks.mine', ['overdue' => 1]), 'error');
            $add('Offene Aufgaben im Aufgabensystem', (clone $mine)->count(), route('tasks.mine'), 'info');
        }

        return $tasks;
    }

    /**
     * Nur die Hinweise, deren Ziel in dem Bereich liegt (für die Übersichtsseiten der Bereiche). Der Bereich eines Hinweises ergibt sich aus der Seite, auf die er verweist.
     *
     * @return list<array{label: string, count: ?int, href: string, tone: string}>
     */
    public static function forArea(User $user, PortalAreas $area): array
    {
        return array_values(array_filter(self::for($user), fn (array $task): bool => self::areaOf($task['href']) === $area));
    }

    private static function areaOf(string $href): ?PortalAreas
    {
        try {
            return PortalNavigation::areaForRoute(app('router')->getRoutes()->match(Request::create($href))->getName());
        } catch (Throwable) {
            return null;
        }
    }
}

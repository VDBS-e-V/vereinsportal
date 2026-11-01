<?php

namespace App\Http\Controllers;

use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessRequests\Enums\AccessRequestStatus;
use App\Modules\AccessRequests\Models\AccessRequest;
use App\Modules\Events\Enums\EventStatus;
use App\Modules\Events\Models\Event;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Models\OnboardingDocument;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\Onboarding\Services\OnboardingDocuments;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarSession;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use App\Support\TaskOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Übersichtsseiten der Bereiche Mitglieder, Vorstand und Bildungsreferenten. Die Übersichten zeigen Zahlen und Termine, keine Personendaten
 * (außer der eigenen Mitgliedschaft im Mitgliederbereich).
 */
class PortalAreaController extends Controller
{
    public function __construct(private readonly OnboardingDocuments $documents) {}

    /** Mitgliederbereich: eigene Mitgliedschaft, Satzung und Willkommensbrief, offene Veranstaltungen, Kontakt zum Vorstand. */
    public function member(Request $request): View
    {
        $user = $request->user();

        return view('portal.member', [
            'person' => $user?->person,
            'membership' => $user?->person?->memberships()->orderByRaw("status IN ('requested','active') DESC")->orderByDesc('id')->first(),
            'documents' => collect(OnboardingDocumentKind::forMembers())->map(fn (OnboardingDocumentKind $kind): array => ['kind' => $kind, 'document' => $this->documents->current($kind)])->all(),
            'events' => Event::query()->where('status', EventStatus::Open->value)->with('sessions')->get()->sortBy(fn (Event $event): string => (string) $event->sessions->first()?->starts_at)->values(),
        ]);
    }

    /** Satzung und Willkommensbrief: nur die jeweils aktuelle Fassung, nur für Mitglieder. */
    public function memberDocument(OnboardingDocument $document): StreamedResponse
    {
        abort_unless(in_array($document->kind, OnboardingDocumentKind::forMembers(), true) && $this->documents->current($document->kind)?->id === $document->id, 404);
        abort_unless(Storage::disk(OnboardingDocuments::DISK)->exists($document->path), 404);

        return Storage::disk(OnboardingDocuments::DISK)->download($document->path, $document->kind->label().'.pdf', ['Content-Type' => 'application/pdf']);
    }

    /** Vorstand: Kennzahlen zu Mitgliedschaften, Anträgen, Teamenden, Seminaren und Veranstaltungen. */
    public function board(): View
    {
        $running = fn ($query) => $query->where('status', MembershipStatus::Active->value)->where(fn ($end) => $end->whereNull('ends_on')->orWhere('ends_on', '>=', today()));

        $teamUsers = RoleAssignment::query()->where('starts_at', '<=', now())->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('role', fn ($query) => $query->where('key', RoleKey::Team->value))->distinct('user_id')->count('user_id');

        return view('portal.board', ['stats' => [
            'members' => $running(Membership::query())->count(),
            'requested' => Membership::query()->where('status', MembershipStatus::Requested->value)->count(),
            'leaving' => $running(Membership::query())->whereNotNull('ends_on')->count(),
            'accessRequests' => AccessRequest::query()->where('status', AccessRequestStatus::New->value)->count(),
            'team' => $teamUsers,
            'onboarding' => TeamOnboarding::query()->whereNull('completed_at')->count(),
            'seminarsPlanning' => Seminar::query()->whereIn('status', [SeminarStatus::Request->value, SeminarStatus::Planning->value])->count(),
            'seminarsPlanned' => Seminar::query()->whereIn('status', [SeminarStatus::Scheduled->value, SeminarStatus::Confirmed->value])->count(),
            'events' => Event::query()->where('status', EventStatus::Open->value)->count(),
        ]]);
    }

    /** Startseite der Bereiche Teamende, Koordination, Geschäftsstelle und Admins: Aufgaben und die Seiten des Bereichs als Karten. */
    public function area(Request $request, string $area): View
    {
        $area = PortalAreas::from($area);
        $user = $request->user();

        abort_unless(in_array($area, PortalNavigation::visibleAreas($user), true), 403);

        $routeName = $request->route()->getName();
        $pages = array_values(array_filter(PortalNavigation::pages($area, $user, $routeName), fn (array $page): bool => ! $page['current']));
        $hrefs = [];

        foreach ($pages as $page) {
            $hrefs = [...$hrefs, $page['href'], ...array_column($page['children'], 'href')];
        }

        return view('portal.area', [
            'area' => $area,
            'pages' => $pages,
            'tasks' => array_values(array_filter(TaskOverview::for($user), fn (array $task): bool => in_array(strtok($task['href'], '?'), array_map(fn (string $href): string => strtok($href, '?'), $hrefs), true))),
        ]);
    }

    /** Bildungsreferenten: Überblick über Seminare und Veranstaltungen (nur lesen). */
    public function education(): View
    {
        return view('portal.education', [
            'byStatus' => collect(SeminarStatus::cases())->mapWithKeys(fn (SeminarStatus $status): array => [$status->value => Seminar::query()->where('status', $status->value)->count()])->all(),
            'sessions' => SeminarSession::query()->where('starts_at', '>', now())->whereHas('seminar', fn ($query) => $query->whereNotIn('status', [SeminarStatus::Cancelled->value, SeminarStatus::Request->value]))
                ->with('seminar.partner')->orderBy('starts_at')->limit(8)->get(),
            'events' => Event::query()->where('status', EventStatus::Open->value)->with('sessions')->withCount(['registrations as confirmed_count' => fn ($query) => $query->whereNotNull('verified_at')->where('waitlisted', false)])->get()
                ->sortBy(fn (Event $event): string => (string) $event->sessions->first()?->starts_at)->values(),
        ]);
    }
}

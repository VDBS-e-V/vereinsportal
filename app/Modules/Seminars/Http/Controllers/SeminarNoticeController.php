<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Mitteilungen von Hand im Seminarablauf: Rückfrage (Geschäftsstelle), Briefing, Onboarding und neue Termine (Koordination). */
class SeminarNoticeController extends Controller
{
    public function __construct(private readonly SeminarNotifications $notices) {}

    public function question(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:1500']], ['text.required' => 'Bitte schreiben Sie Ihre Rückfrage.']);
        $this->notices->question($seminar, $data['text'], $this->actor($request));

        return $this->back($seminar, 'Die Rückfrage ging an die Schule.');
    }

    public function briefing(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['text' => ['nullable', 'string', 'max:2000']]);
        $this->notices->briefing($seminar, $data['text'] ?? null, $this->actor($request));

        return $this->back($seminar, 'Das Briefing ging an die Schule.');
    }

    public function onboarding(Request $request, Seminar $seminar): RedirectResponse
    {
        if (! in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true)) {
            return $this->back($seminar, null, 'Das Onboarding lässt sich nach der Teambestätigung planen.');
        }

        $data = $request->validate([
            'onboarding_at' => ['required', 'date', 'after:now'],
            'onboarding_link' => ['nullable', 'string', 'max:255', 'url:https'],
            'text' => ['nullable', 'string', 'max:1500'],
        ], ['onboarding_at.required' => 'Bitte geben Sie Datum und Uhrzeit an.', 'onboarding_at.after' => 'Der Termin muss in der Zukunft liegen.', 'onboarding_link.url' => 'Der Link muss mit https:// beginnen.']);

        $seminar->forceFill(['onboarding_at' => $data['onboarding_at'], 'onboarding_link' => $data['onboarding_link'] ?? null])->save();
        $this->notices->onboarding($seminar->fresh(), $data['text'] ?? null, $this->actor($request));

        return $this->back($seminar, 'Der Onboarding-Termin ist gespeichert und das Team ist eingeladen.');
    }

    public function rescheduled(Request $request, Seminar $seminar): RedirectResponse
    {
        if (! in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true)) {
            return $this->back($seminar, null, 'Neue Termine melden Sie nach der Teambestätigung.');
        }

        $this->notices->rescheduled($seminar, $this->actor($request));

        return $this->back($seminar, 'Team und Schule wurden über die Termine informiert.');
    }

    private function back(Seminar $seminar, ?string $success, ?string $warning = null): RedirectResponse
    {
        return redirect()->route('seminars.show', $seminar)->with($warning !== null ? 'warning' : 'success', $warning ?? $success);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}

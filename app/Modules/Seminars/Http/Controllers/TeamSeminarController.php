<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\TeamProfile;
use App\Modules\Seminars\Services\TeamApplications;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Seminare für Teamende (Bereich Teamende, Berechtigung seminars.apply): offene Seminare, Bewerbung, eigene Seminare, Kalender. */
class TeamSeminarController extends Controller
{
    public function __construct(private readonly TeamApplications $team) {}

    public function index(Request $request): View
    {
        $user = $this->actor($request);

        $mine = SeminarApplication::query()->where('user_id', $user->id)->with(['seminar.partner', 'seminar.sessions', 'seminar.quotes'])->latest('updated_at')->get()
            ->filter(fn (SeminarApplication $application): bool => $application->seminar !== null);
        $applied = $mine->keyBy('seminar_id');

        $open = $this->team->open()->map(function (Seminar $seminar) use ($user, $applied): array {
            $application = $applied->get($seminar->id);

            return [
                'seminar' => $seminar,
                'application' => $application,
                'canApply' => $application === null || $application->status === ApplicationStatus::Withdrawn,
                'conflicts' => $this->team->conflicts($user, $seminar),
                'honorarium' => $this->honorarium($seminar),
            ];
        });

        return view('seminars.team-index', [
            'open' => $open,
            'mine' => $mine->reject(fn (SeminarApplication $application): bool => $application->status === ApplicationStatus::Withdrawn)->values(),
            'profileEmpty' => (TeamProfile::query()->where('user_id', $user->id)->first()?->isEmpty() ?? true),
            'hasConfirmed' => $mine->contains(fn (SeminarApplication $application): bool => $application->status === ApplicationStatus::Confirmed),
        ]);
    }

    public function apply(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:300'], 'wants_lead' => ['nullable', 'boolean']], ['note.max' => 'Die Nachricht darf höchstens 300 Zeichen lang sein.']);

        try {
            $this->team->apply($seminar, $this->actor($request), $data['note'] ?? null, $request->boolean('wants_lead'));
        } catch (DomainException $exception) {
            return redirect()->route('seminar-applications.index')->withErrors(['apply' => $exception->getMessage()]);
        }

        return redirect()->route('seminar-applications.index')->with('success', 'Ihr Interesse ist eingetragen. Nach dem Stichtag sichtet die Koordination die Rückmeldungen und stellt das Team zusammen.');
    }

    public function withdraw(Request $request, Seminar $seminar): RedirectResponse
    {
        $application = SeminarApplication::query()->where('seminar_id', $seminar->id)->where('user_id', $this->actor($request)->id)->firstOrFail();

        try {
            $this->team->withdraw($application, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('seminar-applications.index')->withErrors(['apply' => $exception->getMessage()]);
        }

        return redirect()->route('seminar-applications.index')->with('success', 'Ihr Interesse wurde zurückgezogen.');
    }

    public function calendar(Request $request): Response
    {
        return response($this->team->calendar($this->actor($request)), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="vdbs-seminare.ics"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Honorar je Teamende und Tag aus dem neuesten Kostenvoranschlag (Überblick, ohne Pauschalen). */
    private function honorarium(Seminar $seminar): ?int
    {
        foreach ($seminar->quotes as $quote) {
            foreach ((array) $quote->positions as $position) {
                foreach ((array) ($position['lines'] ?? []) as $line) {
                    if (($line['key'] ?? null) === 'honorarium') {
                        return (int) $line['unit_cents'];
                    }
                }
            }
        }

        return null;
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}

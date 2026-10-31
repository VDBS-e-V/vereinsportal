<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\TeamApplications;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Bedarf an Teamenden und Entscheidung über Bewerbungen (Bereich Koordination, Berechtigung seminars.manage). */
class SeminarTeamController extends Controller
{
    public function __construct(private readonly TeamApplications $team) {}

    public function need(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['team_needed' => ['nullable', 'integer', 'min:0', 'max:20']], [
            'team_needed.integer' => 'Bitte geben Sie eine ganze Zahl ein.',
            'team_needed.max' => 'Der Bedarf darf höchstens 20 Teamende betragen.',
            'team_needed.min' => 'Der Bedarf darf nicht negativ sein.',
        ]);

        $this->team->setNeed($seminar, ($data['team_needed'] ?? null) !== null ? (int) $data['team_needed'] : null, $this->actor($request));

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Bedarf wurde gespeichert.');
    }

    public function decide(Request $request, Seminar $seminar, SeminarApplication $application, string $decision): RedirectResponse
    {
        abort_unless($application->seminar_id === $seminar->id && in_array($decision, ['bestaetigen', 'ablehnen'], true), 404);

        try {
            $this->team->decide($application, $decision === 'bestaetigen' ? ApplicationStatus::Confirmed : ApplicationStatus::Declined, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('seminars.show', $seminar)->withErrors(['team' => $exception->getMessage()]);
        }

        return redirect()->route('seminars.show', $seminar)->with('success', $decision === 'bestaetigen' ? 'Die Bewerbung wurde bestätigt, die Person wird benachrichtigt.' : 'Die Bewerbung wurde abgelehnt, die Person wird benachrichtigt.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}

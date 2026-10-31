<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\TeamApplications;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Bedarf an Teamenden und Entscheidung über Bewerbungen (Bereich Koordination, Berechtigung seminars.manage). */
class SeminarTeamController extends Controller
{
    public function __construct(private readonly TeamApplications $team) {}

    public function need(Request $request, Seminar $seminar): RedirectResponse
    {
        $needed = (int) $request->input('team_needed', 0);

        $data = $request->validate([
            'team_needed' => ['nullable', 'integer', 'min:0', 'max:20'],
            'application_deadline' => [$needed > 0 ? 'required' : 'nullable', 'date'],
            'team_competences' => ['nullable', 'array'],
            'team_competences.*' => ['string', Rule::in(array_keys((array) config('portal.team.competences', [])))],
        ], [
            'team_needed.integer' => 'Bitte geben Sie eine ganze Zahl ein.',
            'team_needed.max' => 'Der Bedarf darf höchstens 20 Teamende betragen.',
            'team_needed.min' => 'Der Bedarf darf nicht negativ sein.',
            'application_deadline.required' => 'Bitte nennen Sie einen Stichtag, bis zu dem Teamende Interesse bekunden können.',
            'application_deadline.date' => 'Bitte geben Sie ein gültiges Datum ein.',
        ]);

        $this->team->setNeed($seminar, $needed > 0 ? $needed : null, filled($data['application_deadline'] ?? null) ? Carbon::parse($data['application_deadline']) : null, (array) ($data['team_competences'] ?? []), $this->actor($request));

        return redirect()->route('seminars.show', $seminar)->with('success', 'Bedarf, Stichtag und gesuchte Kompetenzen wurden gespeichert.');
    }

    public function note(Request $request, Seminar $seminar, SeminarApplication $application): RedirectResponse
    {
        abort_unless($application->seminar_id === $seminar->id, 404);

        $data = $request->validate(['internal_note' => ['nullable', 'string', 'max:300']], ['internal_note.max' => 'Die Notiz darf höchstens 300 Zeichen lang sein.']);

        $this->team->note($application, $data['internal_note'] ?? null);

        return redirect()->route('seminars.show', $seminar)->with('success', 'Die Notiz wurde gespeichert.');
    }

    public function decide(Request $request, Seminar $seminar, SeminarApplication $application, string $decision): RedirectResponse
    {
        abort_unless($application->seminar_id === $seminar->id && in_array($decision, ['bestaetigen', 'ablehnen'], true), 404);

        try {
            $this->team->decide($application, $decision === 'bestaetigen' ? ApplicationStatus::Confirmed : ApplicationStatus::Declined, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('seminars.show', $seminar)->withErrors(['team' => $exception->getMessage()]);
        }

        return redirect()->route('seminars.show', $seminar)->with('success', $decision === 'bestaetigen' ? 'Die Person ist im Team und wird benachrichtigt.' : 'Die Person wurde nicht berücksichtigt und wird benachrichtigt.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}

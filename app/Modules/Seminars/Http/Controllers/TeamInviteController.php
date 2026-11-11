<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\TeamInvites;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Öffentliche Bewerbung über den allgemeinen Bewerbungslink eines Seminars (ohne Konto, mit E-Mail-Bestätigung). */
class TeamInviteController extends Controller
{
    public function __construct(private readonly TeamInvites $invites) {}

    public function show(string $token): View
    {
        $seminar = $this->invites->seminarFor($token);

        if ($seminar === null) {
            return view('team-invite.closed');
        }

        return view('team-invite.form', ['seminar' => $seminar, 'token' => $token, 'competenceOptions' => (array) config('portal.team.competences', [])]);
    }

    public function store(Request $request, string $token): RedirectResponse|View
    {
        $seminar = $this->invites->seminarFor($token);

        if ($seminar === null) {
            return view('team-invite.closed');
        }

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_year' => ['nullable', 'integer', 'min:1930', 'max:'.((int) now()->format('Y') - 14)],
            'competences' => ['nullable', 'array'],
            'competences.*' => ['string', Rule::in(array_keys((array) config('portal.team.competences', [])))],
            'experience' => ['nullable', 'string', 'max:800'],
            'note' => ['nullable', 'string', 'max:300'],
            'wants_lead' => ['nullable', 'boolean'],
            'consent_contact' => ['nullable', 'boolean'],
            'consent_data' => ['accepted'],
        ], [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'first_name.required' => 'Bitte geben Sie Ihren Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie Ihren Nachnamen ein.',
            'birth_year.min' => 'Bitte prüfen Sie das Geburtsjahr.',
            'birth_year.max' => 'Bitte prüfen Sie das Geburtsjahr.',
            'consent_data.accepted' => 'Bitte stimmen Sie der Verarbeitung Ihrer Angaben zur Teambildung zu.',
        ]);

        try {
            $this->invites->apply($seminar, [
                'email' => $data['email'],
                'name' => trim($data['first_name']).' '.trim($data['last_name']),
                'phone' => $data['phone'] ?? null,
                'birth_year' => $data['birth_year'] ?? null,
                'competences' => $data['competences'] ?? [],
                'experience' => $data['experience'] ?? null,
                'note' => $data['note'] ?? null,
                'wants_lead' => $request->boolean('wants_lead'),
                'consent_contact' => $request->boolean('consent_contact'),
            ]);
        } catch (DomainException) {
            return view('team-invite.closed');
        }

        // Immer dieselbe Antwort, egal ob die Adresse schon bekannt ist.
        return redirect()->route('team-invite.sent');
    }

    public function sent(): View
    {
        return view('team-invite.sent');
    }

    /** Signierter Link aus der Bestätigungsmail. */
    public function confirm(SeminarApplication $application): View
    {
        abort_unless($application->contact_id !== null, 404);

        $this->invites->confirm($application);

        return view('team-invite.confirmed', ['application' => $application->load('seminar')]);
    }
}

<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\TeamGender;
use App\Modules\Seminars\Models\TeamProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Eigenes Teamenden-Profil (Bereich Teamende): Kompetenzen, Erfahrung, Seminarleitung und freiwillige Angabe zum Geschlecht. Sichtbar nur für die Person selbst und die Koordination. */
class TeamProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $this->actor($request);
        $profile = TeamProfile::query()->where('user_id', $user->id)->first();

        return view('seminars.team-profile', ['profile' => $profile, 'competences' => (array) config('portal.team.competences', []), 'genders' => TeamGender::options(), 'birth' => $user->person?->birth_date]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'competences' => ['nullable', 'array'],
            'competences.*' => ['string', Rule::in(array_keys((array) config('portal.team.competences', [])))],
            'experience' => ['nullable', 'string', 'max:1000'],
            'lead_experience' => ['nullable', 'boolean'],
            'gender' => ['nullable', Rule::in(array_keys(TeamGender::options()))],
        ], ['experience.max' => 'Die Beschreibung darf höchstens 1.000 Zeichen lang sein.', 'gender.in' => 'Bitte wählen Sie eine der Angaben.']);

        TeamProfile::query()->updateOrCreate(['user_id' => $this->actor($request)->id], [
            'competences' => array_values((array) ($data['competences'] ?? [])),
            'experience' => filled($data['experience'] ?? null) ? trim($data['experience']) : null,
            'lead_experience' => $request->boolean('lead_experience'),
            'gender' => filled($data['gender'] ?? null) ? $data['gender'] : null,
        ]);

        return redirect()->route('team-profile.edit')->with('success', 'Ihr Profil wurde gespeichert.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}

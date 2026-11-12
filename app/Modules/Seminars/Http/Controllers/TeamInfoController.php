<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\TeamInfo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** „Team informieren“ (Bereich Koordination, Berechtigung seminars.manage). */
class TeamInfoController extends Controller
{
    public function __construct(private readonly TeamInfo $info) {}

    public function show(Seminar $seminar): View|RedirectResponse
    {
        $seminar->load(['partner', 'sessions']);
        $members = $this->info->members($seminar);

        if ($members->isEmpty()) {
            return redirect()->route('seminars.show', $seminar)->withErrors(['team' => 'Es ist noch niemand im Team. Nehmen Sie zuerst Personen auf.']);
        }

        return view('seminars.team-info', [
            'seminar' => $seminar,
            'members' => $members,
            'addresses' => $members->mapWithKeys(fn ($application): array => [$application->id => $this->info->addressFor($application) !== null])->all(),
            'overview' => $this->info->overview($seminar),
            'changed' => $this->info->changedSinceLastInfo($seminar),
        ]);
    }

    public function send(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer'],
            'extra_text' => ['nullable', 'string', 'max:2000'],
        ], ['application_ids.required' => 'Bitte haken Sie mindestens eine Person an.', 'application_ids.min' => 'Bitte haken Sie mindestens eine Person an.']);

        $sent = $this->info->send($seminar, array_map('intval', $data['application_ids']), $data['extra_text'] ?? null);

        if ($sent === 0) {
            return redirect()->route('seminars.team.info', $seminar)->withErrors(['team' => 'Es wurde nichts gesendet. Die gewählten Personen sind nicht im Team oder haben keine erreichbare Adresse.']);
        }

        return redirect()->route('seminars.show', $seminar)->with('success', $sent === 1 ? 'Die Nachricht wurde an 1 Person gesendet.' : "Die Nachricht wurde an {$sent} Personen gesendet.");
    }
}

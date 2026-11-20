<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Teamende: ausgeschriebene Seminare sehen und Interesse mit Verfügbarkeit melden oder zurücknehmen. */
class TeamSeminarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->user($request);

        return view('seminars.team-index', [
            'seminars' => Seminar::query()->where('status', SeminarStatus::Tendered->value)->with(['partner', 'sessions'])->orderBy('application_deadline')->get(),
            'applications' => SeminarApplication::query()->where('user_id', $user->id)->get()->keyBy('seminar_id'),
            'mine' => SeminarApplication::query()->where('user_id', $user->id)->whereNotNull('outcome')->with('seminar.partner', 'seminar.sessions')->orderByDesc('id')->get(),
        ]);
    }

    public function apply(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['availability' => ['nullable', 'string', 'max:1000']]);

        if ($seminar->status !== SeminarStatus::Tendered || ($seminar->application_deadline !== null && $seminar->application_deadline->isPast() && ! $seminar->application_deadline->isToday())) {
            return back()->withErrors(['apply' => 'Für dieses Seminar können Sie sich zurzeit nicht melden.']);
        }

        SeminarApplication::query()->updateOrCreate(
            ['seminar_id' => $seminar->id, 'user_id' => $this->user($request)->id],
            ['availability' => $data['availability'] ?? null, 'status' => SeminarApplication::APPLIED],
        );

        return redirect()->route('seminars.team.index')->with('success', 'Ihr Interesse ist gemeldet. Die Koordination entscheidet und meldet sich bei Ihnen.');
    }

    public function withdraw(Request $request, Seminar $seminar): RedirectResponse
    {
        $application = SeminarApplication::query()->where('seminar_id', $seminar->id)->where('user_id', $this->user($request)->id)->first();

        if ($application === null || $seminar->status !== SeminarStatus::Tendered) {
            return back()->withErrors(['apply' => 'Ihre Meldung lässt sich zurzeit nicht zurücknehmen.']);
        }

        $application->update(['status' => SeminarApplication::WITHDRAWN]);

        return redirect()->route('seminars.team.index')->with('success', 'Ihre Meldung ist zurückgenommen.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

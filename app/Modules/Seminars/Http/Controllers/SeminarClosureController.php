<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarClosures;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Abschluss eines Seminars mit Checkliste (seminars.manage) und Wiederöffnen (seminars.reopen, Vorstand). */
class SeminarClosureController extends Controller
{
    public function __construct(private readonly SeminarClosures $closures) {}

    public function show(Seminar $seminar): View|RedirectResponse
    {
        if ($seminar->status !== SeminarStatus::Done) {
            return redirect()->route('seminars.show', $seminar)->withErrors(['team' => 'Nur ein durchgeführtes Seminar lässt sich abschließen.']);
        }

        return view('seminars.closure', ['seminar' => $seminar->load('partner'), 'checklist' => $this->closures->checklist($seminar), 'snapshot' => $this->closures->snapshot($seminar)]);
    }

    public function store(Request $request, Seminar $seminar): RedirectResponse
    {
        $request->validate(['done' => ['nullable', 'array'], 'skip_reason' => ['nullable', 'array'], 'skip_reason.*' => ['nullable', 'string', 'max:300']]);

        try {
            $this->closures->close($seminar, (array) $request->input('done', []), (array) $request->input('skip_reason', []), $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('seminars.closure', $seminar)->withInput()->withErrors(['closure' => $exception->getMessage()]);
        }

        return redirect()->route('seminars.show', $seminar)->with('success', 'Das Seminar ist abgeschlossen. Die Kennzahlen sind festgehalten.');
    }

    public function reopen(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Bitte nennen Sie eine Begründung für das Wiederöffnen.']);

        try {
            $this->closures->reopen($seminar, $data['reason'], $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('seminars.show', $seminar)->withErrors(['closure' => $exception->getMessage()]);
        }

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Abschluss wurde wieder geöffnet.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}

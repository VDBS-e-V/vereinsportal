<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarDocuments;
use App\Modules\Seminars\Services\SeminarWorkflow;
use App\Modules\Seminars\Support\SeminarLead;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Seminarleitung: Durchführung bestätigen, Verträge und Ergebnisbericht hochladen, Berichte abgeben. Nur für das eigene Seminar. */
class SeminarLeadController extends Controller
{
    /** Arten, die die Seminarleitung hochladen darf. */
    public const KINDS = ['contract' => 'Vertrag (unterschrieben)', 'report' => 'Ergebnisbericht', 'participants' => 'Teilnehmendenzahlen oder -liste', 'other' => 'Sonstiges'];

    public function __construct(private readonly SeminarWorkflow $workflow, private readonly SeminarDocuments $documents) {}

    public function index(Request $request): View
    {
        return view('seminars.lead-index', ['seminars' => SeminarLead::seminars($this->actor($request))]);
    }

    public function show(Request $request, Seminar $seminar): View
    {
        $this->authorizeLead($request, $seminar);

        return view('seminars.lead-show', ['seminar' => $seminar->load(['partner', 'sessions', 'documents']), 'kinds' => self::KINDS]);
    }

    public function held(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->authorizeLead($request, $seminar);

        try {
            $this->workflow->change($seminar, SeminarStatus::Held, $this->actor($request), 'Durchführung von der Seminarleitung bestätigt.');
        } catch (DomainException $exception) {
            return $this->back($seminar, null, $exception->getMessage());
        }

        return $this->back($seminar, 'Danke. Bitte laden Sie jetzt Verträge und Ergebnisbericht hoch.');
    }

    public function upload(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->authorizeLead($request, $seminar);

        if (! in_array($seminar->status, [SeminarStatus::Held, SeminarStatus::Reported], true)) {
            return $this->back($seminar, null, 'Hochladen ist nach der Durchführung möglich.');
        }

        $data = $request->validate([
            'kind' => ['required', 'in:'.implode(',', array_keys(self::KINDS))],
            'title' => ['nullable', 'string', 'max:200'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file'],
        ], ['kind.required' => 'Bitte wählen Sie die Art des Dokuments.', 'files.required' => 'Bitte wählen Sie mindestens eine Datei.']);

        $done = 0;
        $problems = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $this->documents->add($seminar, $file, $data['kind'], $data['title'] ?? null, $this->actor($request));
                $done++;
            } catch (DomainException $exception) {
                $problems[] = $file->getClientOriginalName().': '.$exception->getMessage();
            }
        }

        if ($problems !== []) {
            return redirect()->route('seminar-lead.show', $seminar)->withErrors(['documents' => implode(' ', $problems)])->with($done > 0 ? 'success' : 'info', $done > 0 ? $done.' Datei(en) hochgeladen.' : null);
        }

        return $this->back($seminar, $done.' Datei(en) hochgeladen.');
    }

    public function report(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->authorizeLead($request, $seminar);

        try {
            $this->workflow->change($seminar, SeminarStatus::Reported, $this->actor($request), 'Verträge und Ergebnisbericht abgegeben.');
        } catch (DomainException $exception) {
            return $this->back($seminar, null, $exception->getMessage());
        }

        return $this->back($seminar, 'Danke. Die Koordination prüft den Bericht.');
    }

    private function authorizeLead(Request $request, Seminar $seminar): void
    {
        abort_unless(SeminarLead::is($request->user(), $seminar), 403);
    }

    private function back(Seminar $seminar, ?string $success, ?string $warning = null): RedirectResponse
    {
        return redirect()->route('seminar-lead.show', $seminar)->with($warning !== null ? 'warning' : 'success', $warning ?? $success);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}

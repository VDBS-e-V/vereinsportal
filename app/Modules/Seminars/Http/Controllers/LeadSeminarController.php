<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\SeminarDocuments;
use App\Modules\Seminars\Services\SeminarWorkflow;
use App\Modules\Seminars\Services\TeamContracts;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Seminarleitung: nur das eigene Seminar. Durchführung bestätigen, Bericht und Verträge hochladen, abgeben. */
class LeadSeminarController extends Controller
{
    public function __construct(private readonly SeminarWorkflow $workflow, private readonly SeminarDocuments $documents, private readonly TeamContracts $contracts) {}

    public function index(Request $request): View
    {
        return view('seminars.lead-index', ['seminars' => Seminar::query()->where('lead_user_id', $this->user($request)->id)->with(['partner', 'sessions'])->orderByDesc('id')->get()]);
    }

    public function show(Request $request, Seminar $seminar): View
    {
        $this->own($request, $seminar);

        return view('seminars.lead-show', [
            'seminar' => $seminar->load(['partner', 'sessions', 'documents', 'paymentOrders.user.person', 'applications.user.person']),
            'canHold' => $seminar->status === SeminarStatus::Prepared && $seminar->sessions->max('starts_at')?->isPast(),
        ]);
    }

    public function held(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->own($request, $seminar);

        return $this->run($seminar, fn () => $this->workflow->markHeld($seminar, $this->user($request)), 'Danke. Die Durchführung ist bestätigt. Bitte laden Sie jetzt Bericht und Verträge hoch.');
    }

    /** Ergebnisbericht, Material oder Sonstiges hochladen. */
    public function upload(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->own($request, $seminar);
        $data = $request->validate(['file' => ['required', 'file'], 'kind' => ['required', Rule::in(['report', 'material', 'other'])], 'title' => ['nullable', 'string', 'max:200']], ['file.required' => 'Bitte wählen Sie eine Datei.']);

        if (! in_array($seminar->status, [SeminarStatus::Prepared, SeminarStatus::Held], true)) {
            return back()->withErrors(['lead' => 'Dateien lassen sich nach der Vorbereitung bis zur Abgabe hochladen.']);
        }

        return $this->run($seminar, fn () => $this->documents->add($seminar, $request->file('file'), $data['kind'], $data['title'] ?? null, $this->user($request)), 'Die Datei ist gespeichert.');
    }

    /** Ausgefüllten Vertrag einer teamenden Person hochladen; es entsteht ein Zahlungsauftrag. */
    public function contract(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->own($request, $seminar);
        $data = $request->validate(['member' => ['required', 'integer'], 'file' => ['required', 'file'], 'amount' => ['required', 'string', 'max:20']], ['member.required' => 'Bitte wählen Sie die Person.', 'file.required' => 'Bitte wählen Sie den ausgefüllten Vertrag.', 'amount.required' => 'Bitte geben Sie den Betrag aus dem Vertrag an.']);

        return $this->run($seminar, fn () => $this->contracts->add($seminar, (int) $data['member'], $request->file('file'), $data['amount'], $this->user($request)), 'Der Vertrag ist gespeichert und als Zahlungsauftrag an die Geschäftsstelle übermittelt.');
    }

    public function submit(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->own($request, $seminar);

        return $this->run($seminar, fn () => $this->workflow->submitReport($seminar, $this->user($request)), 'Bericht und Verträge sind abgegeben. Die Koordination prüft sie.');
    }

    private function run(Seminar $seminar, callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $exception) {
            return back()->withErrors(['lead' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.lead.show', $seminar)->with('success', $success);
    }

    /** Nur die Seminarleitung des Seminars; alle anderen sehen eine 404-Seite. */
    private function own(Request $request, Seminar $seminar): void
    {
        abort_unless($seminar->lead_user_id !== null && $seminar->lead_user_id === $this->user($request)->id, 404);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

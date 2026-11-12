<?php

namespace App\Modules\Board\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardResolution;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Beschlüsse des Vorstands: nummeriert je Jahr (B-2026-001), aus einer Sitzung erfasst, als Gesamtliste durchsuchbar. */
class BoardResolutionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'year' => ['nullable', 'integer'], 'outcome' => ['nullable', Rule::in(array_keys(BoardResolution::OUTCOMES))]]);

        $resolutions = BoardResolution::query()->with('meeting')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($inner) => $inner->where('title', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('text', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->where('year', $year))
            ->when($filters['outcome'] ?? null, fn ($query, $outcome) => $query->where('outcome', $outcome))
            ->orderByDesc('year')->orderByDesc('seq')->limit(500)->get();

        return view('board.resolutions.index', [
            'resolutions' => $resolutions, 'filters' => $filters, 'outcomes' => BoardResolution::OUTCOMES,
            'years' => BoardResolution::query()->distinct()->orderByDesc('year')->pluck('year')->mapWithKeys(fn ($year) => [$year => (string) $year])->all(),
        ]);
    }

    public function store(Request $request, BoardMeeting $meeting): RedirectResponse
    {
        $data = $this->validated($request);

        $resolution = DB::transaction(function () use ($data, $meeting): BoardResolution {
            $year = (int) $meeting->starts_at->format('Y');
            $seq = (int) BoardResolution::query()->where('year', $year)->lockForUpdate()->max('seq') + 1;

            return BoardResolution::query()->create($data + ['board_meeting_id' => $meeting->id, 'year' => $year, 'seq' => $seq, 'created_by' => $this->user()->id]);
        });

        AuditLog::record(AuditEvent::BoardResolutionSaved, $this->user(), null, ['beschluss' => $resolution->number(), 'sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y')]);

        return redirect()->to(route('board.meetings.show', $meeting).'#resolutions-heading')->with('success', 'Der Beschluss '.$resolution->number().' wurde erfasst.');
    }

    public function edit(BoardResolution $resolution): View
    {
        return view('board.resolutions.form', ['resolution' => $resolution->load('meeting'), 'outcomes' => BoardResolution::OUTCOMES]);
    }

    public function update(Request $request, BoardResolution $resolution): RedirectResponse
    {
        $resolution->update($this->validated($request));
        AuditLog::record(AuditEvent::BoardResolutionSaved, $this->user(), null, ['beschluss' => $resolution->number()]);

        return redirect()->to(route('board.meetings.show', $resolution->board_meeting_id).'#resolutions-heading')->with('success', 'Der Beschluss wurde gespeichert.');
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], ['ids.required' => 'Bitte wählen Sie mindestens einen Beschluss aus.'])['ids'];
        $resolutions = BoardResolution::query()->whereIn('id', $ids)->get();

        foreach ($resolutions as $resolution) {
            AuditLog::record(AuditEvent::BoardResolutionDeleted, $this->user(), null, ['beschluss' => $resolution->number()]);
            $resolution->delete();
        }

        return back()->with('success', $resolutions->count() === 1 ? 'Der Beschluss wurde gelöscht.' : $resolutions->count().' Beschlüsse wurden gelöscht.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'text' => ['nullable', 'string', 'max:10000'],
            'outcome' => ['required', Rule::in(array_keys(BoardResolution::OUTCOMES))],
            'votes_for' => ['nullable', 'integer', 'min:0', 'max:999'], 'votes_against' => ['nullable', 'integer', 'min:0', 'max:999'], 'votes_abstain' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], ['title.required' => 'Bitte geben Sie dem Beschluss einen Titel.', 'outcome.required' => 'Bitte wählen Sie das Ergebnis.']);

        foreach (['votes_for', 'votes_against', 'votes_abstain'] as $key) {
            $data[$key] = $data[$key] ?? null;
        }

        return $data;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

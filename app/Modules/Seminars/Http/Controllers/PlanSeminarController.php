<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Koordination: freigegebene Seminare planen, ausschreiben und absagen. */
class PlanSeminarController extends Controller
{
    public const SESSION_ROWS = 8;

    public function __construct(private readonly SeminarWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $status = SeminarStatus::tryFrom((string) $request->query('status', ''));
        $coordination = $request->integer('coordination') ?: null;
        $visible = array_values(array_diff(SeminarStatus::cases(), [SeminarStatus::Request, SeminarStatus::InReview, SeminarStatus::QuoteSent, SeminarStatus::Rejected]));

        $seminars = Seminar::query()->with(['partner', 'coordination', 'sessions'])
            ->whereIn('status', array_map(fn (SeminarStatus $case): string => $case->value, $visible))
            ->when($status, fn ($query, SeminarStatus $value) => $query->where('status', $value->value))
            ->when($coordination, fn ($query, int $id) => $query->where('coordination_id', $id))
            ->orderByDesc('id')->get();

        return view('seminars.plan-index', [
            'seminars' => $seminars,
            'status' => $status?->value,
            'coordination' => $coordination,
            'statusOptions' => collect($visible)->mapWithKeys(fn (SeminarStatus $case): array => [$case->value => $case->label()])->all(),
            'coordinationOptions' => Coordination::query()->ordered()->pluck('name', 'id')->all(),
        ]);
    }

    public function show(Request $request, Seminar $seminar): View
    {
        abort_if(in_array($seminar->status, [SeminarStatus::Request, SeminarStatus::InReview, SeminarStatus::QuoteSent, SeminarStatus::Rejected], true), 404);

        return view('seminars.plan-show', [
            'seminar' => $seminar->load(['partner', 'coordination', 'sessions', 'events.actor', 'applications.user.person']),
            'rows' => self::SESSION_ROWS,
            'canCancel' => $this->workflow->can($seminar, SeminarStatus::Cancelled, $this->user($request)),
        ]);
    }

    public function plan(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'team_target' => ['required', 'integer', 'min:1', 'max:50'],
            'sessions' => ['nullable', 'array', 'max:'.self::SESSION_ROWS],
        ], ['team_target.required' => 'Bitte geben Sie die Sollzahl des Teams an.', 'team_target.integer' => 'Bitte geben Sie die Sollzahl als ganze Zahl an.']);

        $sessions = $this->parseSessions((array) ($data['sessions'] ?? []));

        try {
            $this->workflow->plan($seminar, $sessions, (int) $data['team_target'], $this->user($request));
        } catch (DomainException $exception) {
            return back()->withErrors(['plan' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Termine und Sollzahl sind gespeichert.');
    }

    public function tender(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['application_deadline' => ['required', 'date']], ['application_deadline.required' => 'Bitte geben Sie die Bewerbungsfrist an.']);

        try {
            $this->workflow->tender($seminar, Carbon::parse($data['application_deadline']), $this->user($request));
        } catch (DomainException $exception) {
            return back()->withErrors(['application_deadline' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Das Seminar ist für das Team ausgeschrieben.');
    }

    public function cancel(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']], ['comment.required' => 'Bitte geben Sie eine Begründung an.']);

        try {
            $this->workflow->transition($seminar, SeminarStatus::Cancelled, $this->user($request), $data['comment']);
        } catch (DomainException $exception) {
            return back()->withErrors(['comment' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Das Seminar ist abgesagt.');
    }

    /**
     * Termine aus dem Formular (Datum, von, bis, Ort). Leere Zeilen entfallen.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array{starts_at: Carbon, ends_at: ?Carbon, location: ?string}>
     */
    private function parseSessions(array $rows): array
    {
        $sessions = [];

        foreach ($rows as $index => $row) {
            $row = (array) $row;
            $date = trim((string) ($row['date'] ?? ''));
            $from = trim((string) ($row['from'] ?? ''));
            $to = trim((string) ($row['to'] ?? ''));
            $location = trim((string) ($row['location'] ?? ''));

            if ($date === '' && $from === '' && $to === '' && $location === '') {
                continue;
            }

            $start = $date !== '' && preg_match('/^\d{2}:\d{2}$/', $from) === 1 ? Carbon::createFromFormat('Y-m-d H:i', $date.' '.$from) : null;

            if (! $start instanceof Carbon) {
                throw ValidationException::withMessages(['sessions.'.$index.'.date' => 'Bitte geben Sie Datum und Beginn an (Beginn zum Beispiel 09:00).']);
            }

            $end = null;

            if ($to !== '') {
                $end = preg_match('/^\d{2}:\d{2}$/', $to) === 1 ? Carbon::createFromFormat('Y-m-d H:i', $date.' '.$to) : null;

                if (! $end instanceof Carbon || $end->lessThanOrEqualTo($start)) {
                    throw ValidationException::withMessages(['sessions.'.$index.'.to' => 'Das Ende muss nach dem Beginn liegen (zum Beispiel 13:30).']);
                }
            }

            $sessions[] = ['starts_at' => $start, 'ends_at' => $end, 'location' => $location === '' ? null : mb_substr($location, 0, 200)];
        }

        usort($sessions, fn (array $a, array $b): int => $a['starts_at']->getTimestamp() <=> $b['starts_at']->getTimestamp());

        return $sessions;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

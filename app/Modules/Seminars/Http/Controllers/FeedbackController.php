<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarFeedback;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Rückmeldung der Schule (Formular im Bereich Schulen) und Auswertung für die Koordination. */
class FeedbackController extends Controller
{
    public function __construct(private readonly SchoolAccounts $accounts, private readonly SeminarWorkflow $workflow) {}

    public function store(Request $request, Seminar $seminar): RedirectResponse
    {
        $user = $this->user($request);
        $account = $this->accounts->forUser($user);
        abort_unless($account !== null && $seminar->partner_id === $account->partner_id, 404);

        $rules = ['comment' => ['nullable', 'string', 'max:3000']];

        foreach (array_keys(SeminarFeedback::QUESTIONS) as $key) {
            $rules['ratings.'.$key] = ['required', 'integer', 'between:1,5'];
        }

        $data = $request->validate($rules, ['ratings.*.required' => 'Bitte bewerten Sie alle Fragen.', 'ratings.*.between' => 'Bitte wählen Sie eine Bewertung von 1 bis 5.', 'ratings.*.integer' => 'Bitte wählen Sie eine Bewertung von 1 bis 5.']);

        if ($seminar->report_sent_at === null) {
            return back()->withErrors(['feedback' => 'Die Rückmeldung ist möglich, sobald Sie den Bericht erhalten haben.']);
        }

        if (SeminarFeedback::query()->where('seminar_id', $seminar->id)->exists()) {
            return back()->withErrors(['feedback' => 'Zu diesem Seminar haben Sie schon eine Rückmeldung gegeben. Danke!']);
        }

        SeminarFeedback::query()->create(['seminar_id' => $seminar->id, 'user_id' => $user->id, 'ratings' => array_map('intval', $data['ratings']), 'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null]);
        $this->workflow->log($seminar, 'feedback', $user, null, null, 'Rückmeldung der Schule eingegangen');

        return redirect()->route('school.seminars.show', $seminar)->with('success', 'Danke für Ihre Rückmeldung.');
    }

    /** Auswertung: Durchschnitt je Frage und alle Rückmeldungen, nach Koordination und Schuljahr filterbar. */
    public function index(Request $request): View
    {
        $coordination = $request->integer('coordination') ?: null;
        $year = preg_match('~^\d{4}/\d{2}$~', (string) $request->query('school_year', '')) === 1 ? (string) $request->query('school_year') : null;

        $feedback = SeminarFeedback::query()->with('seminar.partner')
            ->whereHas('seminar', fn ($query) => $query->when($coordination, fn ($inner, int $id) => $inner->where('coordination_id', $id))->when($year, fn ($inner, string $value) => $inner->where('school_year', $value)))
            ->orderByDesc('id')->get();

        $averages = [];

        foreach (array_keys(SeminarFeedback::QUESTIONS) as $key) {
            $values = $feedback->map(fn (SeminarFeedback $row): ?int => $row->rating($key))->filter(fn (?int $value): bool => $value !== null);
            $averages[$key] = $values->isEmpty() ? null : round((float) $values->avg(), 1);
        }

        return view('seminars.feedback-index', [
            'feedback' => $feedback,
            'averages' => $averages,
            'coordination' => $coordination,
            'schoolYear' => $year,
            'coordinationOptions' => Coordination::query()->ordered()->pluck('name', 'id')->all(),
            'schoolYears' => Seminar::query()->whereNotNull('school_year')->distinct()->orderByDesc('school_year')->pluck('school_year', 'school_year')->all(),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

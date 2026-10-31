<?php

namespace App\Http\Controllers;

use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Http\Controllers\SeminarQuoteController;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Services\SchoolRequests;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Bereich Schulen: Konten mit der Rolle Schule sehen nur Seminare und Kostenvoranschläge der eigenen Schule, können ein Seminar anfragen,
 * Termin und Kostenvoranschlag bestätigen und der Koordination eine Rückmeldung schicken. Alles andere bleibt der Koordination vorbehalten.
 */
class SchoolAreaController extends Controller
{
    public function __construct(private readonly SchoolAccounts $accounts, private readonly SchoolRequests $requests) {}

    public function home(Request $request): View
    {
        $account = $this->account($request);

        $seminars = Seminar::query()->where('partner_id', $account->partner_id)->whereNotIn('status', [SeminarStatus::Cancelled->value])->with(['sessions', 'quotes'])
            ->orderByDesc('updated_at')->get();

        return view('school.home', [
            'account' => $account,
            'seminars' => $seminars,
            'confirmed' => $seminars->mapWithKeys(fn (Seminar $seminar): array => [$seminar->id => $this->requests->confirmed($seminar)]),
        ]);
    }

    public function create(Request $request): View
    {
        return view('school.request', ['account' => $this->account($request), 'types' => SeminarType::options()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = $this->account($request);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(SeminarType::options()))],
            'wished_period' => ['nullable', 'string', 'max:200'],
            'expected_participants' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'type.required' => 'Bitte wählen Sie, was Sie anfragen möchten.',
            'type.in' => 'Bitte wählen Sie eine der Leistungen.',
            'expected_participants.integer' => 'Bitte geben Sie eine ganze Zahl ein.',
        ]);

        $this->requests->request($account, $data);

        return redirect()->route('school.home')->with('success', 'Ihre Anfrage ist bei der Koordination eingegangen. Sie meldet sich bei Ihnen.');
    }

    /** Der Kostenvoranschlag der eigenen Schule als Druckansicht (immer die aktuelle Fassung). */
    public function quote(Request $request, SeminarQuote $quote): View
    {
        $account = $this->account($request);

        abort_unless($quote->partner_id === $account->partner_id, 404);

        $request->query->remove('fassung');

        return app(SeminarQuoteController::class)->print($request, $quote)->with('back', route('school.home'));
    }

    public function confirm(Request $request, Seminar $seminar): RedirectResponse
    {
        $account = $this->account($request);

        abort_unless($seminar->partner_id === $account->partner_id, 404);

        try {
            $this->requests->confirm($seminar, $account);
        } catch (DomainException $exception) {
            return redirect()->route('school.home')->withErrors(['school' => $exception->getMessage()]);
        }

        return redirect()->route('school.home')->with('success', 'Danke! Die Koordination weiß jetzt, dass Sie Termin und Kostenvoranschlag bestätigt haben.');
    }

    public function feedback(Request $request, Seminar $seminar): RedirectResponse
    {
        $account = $this->account($request);

        abort_unless($seminar->partner_id === $account->partner_id, 404);

        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], ['note.required' => 'Bitte schreiben Sie Ihre Rückmeldung.', 'note.max' => 'Die Rückmeldung darf höchstens 1.000 Zeichen lang sein.']);

        $this->requests->feedback($seminar, $account, $data['note']);

        return redirect()->route('school.home')->with('success', 'Ihre Rückmeldung wurde an die Koordination geschickt.');
    }

    private function account(Request $request): PartnerUser
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $account = $this->accounts->forUser($user);

        abort_if($account === null, 403, 'Ihr Konto ist keiner Schule zugeordnet.');

        return $account;
    }
}

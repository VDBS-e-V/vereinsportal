<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Bereich Schulen: Seminar anfragen und den Stand der eigenen Anfragen sehen. */
class SchoolSeminarController extends Controller
{
    public function __construct(private readonly SchoolAccounts $accounts, private readonly SeminarWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $account = $this->account($request);

        return view('seminars.school-index', [
            'account' => $account,
            'seminars' => Seminar::query()->where('partner_id', $account->partner_id)->with('sessions')->orderByDesc('id')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $account = $this->account($request);
        $user = $this->user($request);

        return view('seminars.school-form', ['account' => $account, 'defaults' => ['contact_name' => (string) $user->person?->fullName(), 'contact_email' => (string) $user->email]]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = $this->account($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'format' => ['nullable', 'string', 'max:120'],
            'participants' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'desired_period' => ['nullable', 'string', 'max:200'],
            'request_note' => ['nullable', 'string', 'max:3000'],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['required', 'string', 'email', 'max:254'],
        ], ['title.required' => 'Bitte nennen Sie das Thema oder den Titel.', 'contact_name.required' => 'Bitte nennen Sie eine Ansprechperson.', 'contact_email.required' => 'Bitte geben Sie eine E-Mail-Adresse an.']);

        $seminar = $this->workflow->request($account->partner, $data, $this->user($request));

        return redirect()->route('school.seminars.show', $seminar)->with('success', 'Ihre Anfrage ist eingegangen. Die Geschäftsstelle prüft sie und meldet sich bei Ihnen.');
    }

    public function show(Request $request, Seminar $seminar): View
    {
        $account = $this->account($request);
        abort_unless($seminar->partner_id === $account->partner_id, 404);

        return view('seminars.school-show', ['account' => $account, 'seminar' => $seminar->load('sessions')]);
    }

    private function account(Request $request): PartnerUser
    {
        $account = $this->accounts->forUser($this->user($request));

        abort_if($account === null, 403, 'Ihr Konto ist keiner Schule zugeordnet.');

        return $account;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

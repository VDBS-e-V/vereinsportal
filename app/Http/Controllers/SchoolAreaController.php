<?php

namespace App\Http\Controllers;

use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Bereich Schulen: Konten mit der Rolle Schule sehen nur Seminare und Kostenvoranschläge der eigenen Schule, können ein Seminar anfragen,
 * Termin und Kostenvoranschlag bestätigen und der Koordination eine Rückmeldung schicken. Alles andere bleibt der Koordination vorbehalten.
 */
class SchoolAreaController extends Controller
{
    public function __construct(private readonly SchoolAccounts $accounts) {}

    public function home(Request $request): View
    {
        return view('school.home', ['account' => $this->account($request)]);
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

<?php

namespace App\Modules\Events\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Events\Models\EventRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/** „Meine Tickets“ im Konto: die Anmeldungen zu Veranstaltungen, die mit der E-Mail-Adresse des Kontos (oder der Person) gemacht wurden, mit Ticketcode und Stand. */
class AccountTicketController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $emails = array_values(array_unique(array_filter([mb_strtolower((string) $user->email), mb_strtolower((string) $user->person?->email)])));

        $registrations = EventRegistration::query()->whereIn('email', $emails)->with(['event.sessions'])->orderByDesc('id')->get();

        return view('account.tickets', ['registrations' => $registrations]);
    }
}

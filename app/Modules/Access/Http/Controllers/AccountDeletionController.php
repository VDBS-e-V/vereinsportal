<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\AccountDeletion;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Konto > Konto löschen: Löschung beantragen und (per signiertem Link) wieder abbrechen. */
class AccountDeletionController extends Controller
{
    public const PHRASE = 'LÖSCHEN';

    public function __construct(private readonly AccountDeletion $deletion) {}

    public function show(Request $request): View
    {
        $user = $this->user($request);

        return view('account.deletion', [
            'blockers' => $this->deletion->blockers($user),
            'graceDays' => AccountDeletion::graceDays(),
            'phrase' => self::PHRASE,
            'hasMembership' => $user->person?->memberships()->open()->exists() ?? false,
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $user = $this->user($request);

        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:'.self::PHRASE],
        ], [
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
            'password.current_password' => 'Das Passwort ist nicht korrekt.',
            'confirmation.required' => 'Bitte tippen Sie „'.self::PHRASE.'“ zur Bestätigung.',
            'confirmation.in' => 'Bitte tippen Sie „'.self::PHRASE.'“ zur Bestätigung.',
        ]);

        try {
            $this->deletion->request($user);
        } catch (DomainException $exception) {
            return redirect()->route('account.deletion')->with('warning', $exception->getMessage());
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Die Löschung Ihres Kontos wurde beantragt. Sie erhalten eine E-Mail, mit der Sie sie innerhalb von '.AccountDeletion::graceDays().' Tagen abbrechen können.');
    }

    /** Öffentlich über den signierten Link aus der E-Mail: zeigt die Bestätigung zum Abbrechen. */
    public function cancelShow(Request $request, User $user): View
    {
        return view('account.deletion-cancel', ['pending' => $user->status->value === 'pending_deletion', 'action' => $request->fullUrl()]);
    }

    public function cancelStore(User $user): View
    {
        try {
            $this->deletion->cancel($user);
        } catch (DomainException) {
            return view('account.deletion-cancel', ['pending' => false, 'action' => null, 'done' => false]);
        }

        return view('account.deletion-cancel', ['pending' => false, 'action' => null, 'done' => true]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

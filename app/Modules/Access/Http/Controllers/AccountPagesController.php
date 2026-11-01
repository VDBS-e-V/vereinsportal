<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\Profiles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Konto › Übersicht und Konto › Mitgliedschaft (nur lesen). */
class AccountPagesController extends Controller
{
    public function __construct(private readonly Profiles $profiles) {}

    public function home(Request $request): View
    {
        $user = $this->user($request);
        $hints = [];

        if ($user->twoFactorMethods()->whereNotNull('confirmed_at')->whereNull('disabled_at')->doesntExist()) {
            $hints[] = ['text' => 'Zwei-Faktor ist noch nicht eingerichtet.', 'href' => route('account.security.index')];
        }

        if ($user->email_verified_at === null) {
            $hints[] = ['text' => 'Ihre E-Mail-Adresse ist noch nicht bestätigt.', 'href' => route('account.email')];
        }

        return view('account.home', [
            'user' => $user,
            'hasProfile' => $this->profiles->allowedFor($user),
            'completeness' => $this->profiles->allowedFor($user) ? $this->profiles->completeness($user) : null,
            'hints' => $hints,
        ]);
    }

    public function roles(Request $request): View
    {
        $user = $this->user($request);

        return view('account.roles', [
            'user' => $user,
            'membership' => $user->person?->memberships()->orderByRaw("status IN ('requested','active') DESC")->orderByDesc('id')->first(),
            'assignments' => $user->roleAssignments()->with('role')->orderByDesc('starts_at')->get(),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

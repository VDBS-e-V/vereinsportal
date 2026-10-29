<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Middleware\EnsureSessionVersion;
use App\Modules\Access\Models\User;
use Illuminate\Contracts\View\View;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** Konto > Passwort: neues Passwort setzen. Alle anderen Sitzungen werden dabei beendet. */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.required' => 'Bitte geben Sie Ihr aktuelles Passwort ein.',
            'current_password.current_password' => 'Das aktuelle Passwort ist nicht korrekt.',
            'password.required' => 'Bitte geben Sie ein neues Passwort ein.',
            'password.confirmed' => 'Die beiden Passwörter stimmen nicht überein.',
            'password.different' => 'Das neue Passwort muss sich vom aktuellen unterscheiden.',
            'password.min' => 'Das Passwort muss mindestens 12 Zeichen lang sein.',
            'password.mixed' => 'Das Passwort braucht Groß- und Kleinbuchstaben.',
            'password.numbers' => 'Das Passwort braucht mindestens eine Ziffer.',
        ]);

        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'remember_token' => Str::random(60),
            'session_version' => $user->session_version + 1,
            'force_password_change_at' => null,
        ])->save();

        // Diese Sitzung bleibt angemeldet, alle anderen enden beim nächsten Aufruf.
        $request->session()->put(EnsureSessionVersion::KEY, (int) $user->session_version);
        $request->session()->regenerate();
        AuditLog::record(AuditEvent::PasswordChanged, $user, $user);

        return redirect()->route('account.password')->with('success', 'Ihr Passwort wurde geändert. Alle anderen Sitzungen wurden abgemeldet.');
    }
}

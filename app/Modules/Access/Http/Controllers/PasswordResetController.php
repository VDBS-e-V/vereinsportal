<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Passwort vergessen: Link per E-Mail anfordern und über den Link ein neues Passwort setzen.
 * Die Antwort auf die Anforderung ist immer dieselbe und verrät nicht, ob ein Konto existiert.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('access.password.request');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email']], [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        ]);

        Password::broker()->sendResetLink(['email' => $data['email'], 'status' => UserStatus::Active->value]);

        return back()->with('success', 'Wenn zu dieser Adresse ein aktives Konto besteht, haben wir Ihnen einen Link zum Zurücksetzen gesendet.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('access.password.reset', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'password.required' => 'Bitte geben Sie ein neues Passwort ein.',
            'password.confirmed' => 'Die beiden Passwörter stimmen nicht überein.',
            'password.min' => 'Das Passwort muss mindestens 12 Zeichen lang sein.',
            'password.mixed' => 'Das Passwort braucht Groß- und Kleinbuchstaben.',
            'password.numbers' => 'Das Passwort braucht mindestens eine Ziffer.',
        ]);

        $status = Password::broker()->reset(
            ['email' => $data['email'], 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token'], 'status' => UserStatus::Active->value],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    // beendet alle bestehenden Sitzungen dieses Kontos
                    'session_version' => $user->session_version + 1,
                    'force_password_change_at' => null,
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Der Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Ihr Passwort wurde geändert. Sie können sich jetzt anmelden.');
    }
}

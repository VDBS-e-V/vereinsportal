<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Http\Middleware\EnsureSessionVersion;
use App\Modules\Access\Http\Requests\LoginRequest;
use App\Modules\Access\Services\EmailChallenges;
use App\Modules\Access\Services\TwoFactorManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('access.login');
    }

    /**
     * Anmeldung mit E-Mail und Passwort. Hat das Konto eine Zwei-Faktor-Methode, ist der Nutzer danach noch nicht
     * angemeldet: Die Sitzung merkt sich nur, wer sich gerade anmeldet (zehn Minuten), bis der Code bestätigt ist.
     */
    public function store(LoginRequest $request, TwoFactorManager $twoFactor, EmailChallenges $emailChallenges): RedirectResponse
    {
        $user = $request->validatedUser();

        $request->session()->regenerate();

        if ($twoFactor->isEnabled($user)) {
            $request->session()->put('two_factor', [
                'user_id' => $user->id,
                'remember' => $request->boolean('remember'),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            if ($twoFactor->defaultType($user) === TwoFactorMethodType::Email) {
                $emailChallenges->send($user);
            }

            return redirect()->route('two-factor.challenge');
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->put(EnsureSessionVersion::KEY, (int) $user->session_version);

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

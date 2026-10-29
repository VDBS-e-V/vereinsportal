<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Http\Middleware\EnsureSessionVersion;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\EmailChallenges;
use App\Modules\Access\Services\TwoFactorManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Zweiter Schritt der Anmeldung: Code der Authenticator-App, E-Mail-Code oder Wiederherstellungscode.
 * Erreichbar nur nach erfolgreicher Passwort-Prüfung (Sitzungswert two_factor, zehn Minuten gültig).
 */
class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly TwoFactorManager $twoFactor,
        private readonly EmailChallenges $emailChallenges,
    ) {}

    public function create(Request $request): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user instanceof User) {
            return $this->backToLogin($request);
        }

        $requested = $request->query('method');

        $method = match (true) {
            $requested === 'recovery' => 'recovery',
            $requested === 'email' && $this->twoFactor->has($user, TwoFactorMethodType::Email) => 'email',
            $requested === 'totp' && $this->twoFactor->has($user, TwoFactorMethodType::Totp) => 'totp',
            default => $this->twoFactor->defaultType($user)?->value ?? 'recovery',
        };

        return view('access.two-factor.challenge', [
            'method' => $method,
            'hasTotp' => $this->twoFactor->has($user, TwoFactorMethodType::Totp),
            'hasEmail' => $this->twoFactor->has($user, TwoFactorMethodType::Email),
            'email' => $user->email,
            'resendIn' => $this->emailChallenges->secondsUntilResend($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user instanceof User) {
            return $this->backToLogin($request);
        }

        $data = $request->validate([
            'method' => ['required', 'in:totp,email,recovery'],
            'otp' => ['required', 'string', 'max:64'],
        ], [
            'otp.required' => 'Bitte geben Sie den Code ein.',
        ]);

        $key = 'two-factor|'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'otp' => 'Zu viele Versuche. Bitte versuchen Sie es in '.ceil(RateLimiter::availableIn($key) / 60).' Minute(n) erneut.',
            ]);
        }

        $valid = match ($data['method']) {
            'recovery' => $this->twoFactor->verifyRecovery($user, $data['otp']),
            default => $this->twoFactor->verify($user, TwoFactorMethodType::from($data['method']), $data['otp']),
        };

        if (! $valid) {
            RateLimiter::hit($key, 300);

            return redirect()
                ->route('two-factor.challenge', ['method' => $data['method']])
                ->withErrors(['otp' => $data['method'] === 'recovery'
                    ? 'Der Wiederherstellungscode ist nicht gültig oder wurde schon verwendet.'
                    : 'Der Code ist nicht gültig oder abgelaufen.']);
        }

        RateLimiter::clear($key);

        $remember = (bool) $request->session()->get('two_factor.remember', false);

        $request->session()->forget('two_factor');
        $request->session()->regenerate();

        Auth::guard('web')->login($user, $remember);
        $request->session()->put(EnsureSessionVersion::KEY, (int) $user->session_version);

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('home'));
    }

    /** Sendet den E-Mail-Code erneut (höchstens einmal pro Minute). */
    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user instanceof User || ! $this->twoFactor->has($user, TwoFactorMethodType::Email)) {
            return $this->backToLogin($request);
        }

        $sent = $this->emailChallenges->send($user);

        return redirect()
            ->route('two-factor.challenge', ['method' => 'email'])
            ->with($sent ? 'success' : 'warning', $sent
                ? 'Wir haben Ihnen einen neuen Code per E-Mail gesendet.'
                : 'Bitte warten Sie kurz, bevor Sie einen weiteren Code anfordern.');
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('two_factor');

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::query()->find($pending['user_id'] ?? 0);

        return $user instanceof User && $user->status === UserStatus::Active && $this->twoFactor->isEnabled($user) ? $user : null;
    }

    private function backToLogin(Request $request): RedirectResponse
    {
        $request->session()->forget('two_factor');

        return redirect()->route('login')->with('warning', 'Die Anmeldung ist abgelaufen. Bitte melden Sie sich erneut an.');
    }
}

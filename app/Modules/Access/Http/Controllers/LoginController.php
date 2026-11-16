<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Http\Middleware\EnsureSessionVersion;
use App\Modules\Access\Http\Requests\LoginRequest;
use App\Modules\Access\Services\EmailChallenges;
use App\Modules\Access\Services\TwoFactorManager;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Sso\Services\ConnectedServices;
use App\Modules\Sso\Services\OAuthClients;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create(Request $request, OAuthClients $clients): View
    {
        return view('access.login', ['service' => $this->service($request, $clients)]);
    }

    /**
     * Für welchen Dienst meldet sich die Person gerade an? Kommt sie von einer Anmeldeanfrage eines angebundenen Systems (/oauth/authorize),
     * zeigt die Seite dessen Namen und Beschreibung („Beschreibung des Dienstes“).
     *
     * @return array{name: string, description: ?string}|null
     */
    private function service(Request $request, OAuthClients $clients): ?array
    {
        $intended = (string) $request->session()->get('url.intended', '');
        $parts = parse_url($intended);

        if (! is_array($parts) || ($parts['path'] ?? '') !== '/oauth/authorize') {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $client = filled($query['client_id'] ?? null) ? $clients->find((string) $query['client_id']) : null;

        if ($client === null || $client->revoked) {
            return null;
        }

        return ['name' => (string) $client->name, 'description' => $clients->settings($client)->description];
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
        AuditLog::record(AuditEvent::Login, $user, $user, ['zweiter_faktor' => false]);

        return redirect()->intended(route('home'));
    }

    /**
     * Abmelden vom Portal. Sind angebundene Systeme mit Abmelde-Adresse angemeldet, meldet der Browser dort ebenfalls ab (Seite mit der Liste der Systeme).
     */
    public function destroy(Request $request, ConnectedServices $services): RedirectResponse|View
    {
        $user = $request->user();
        $others = $user !== null ? $services->withLogout($user) : [];

        if ($user !== null) {
            $services->revokeAll($user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($others === []) {
            return redirect()->route('login');
        }

        return view('sso.logout-done', ['services' => $others, 'next' => route('login')]);
    }
}

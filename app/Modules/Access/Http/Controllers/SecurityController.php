<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\EmailChallenges;
use App\Modules\Access\Services\Totp;
use App\Modules\Access\Services\TwoFactorManager;
use App\Modules\Access\Services\TwoFactorPolicy;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Konto > Sicherheit: Zwei-Faktor-Methoden einrichten und entfernen, Wiederherstellungscodes erzeugen.
 * Folgenreiche Änderungen (entfernen, neue Codes) verlangen das aktuelle Passwort.
 */
class SecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorManager $twoFactor,
        private readonly EmailChallenges $emailChallenges,
        private readonly TwoFactorPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);

        return view('account.security.index', [
            'hasTotp' => $this->twoFactor->has($user, TwoFactorMethodType::Totp),
            'hasEmail' => $this->twoFactor->has($user, TwoFactorMethodType::Email),
            'enabled' => $this->twoFactor->isEnabled($user),
            'remaining' => $this->twoFactor->remainingRecoveryCodes($user),
            'email' => $user->email,
            'required' => $this->policy->isRequired($user),
        ]);
    }

    /** Einrichtung der Authenticator-App: Secret anzeigen, Code zur Bestätigung abfragen. */
    public function totpCreate(Request $request): View|RedirectResponse
    {
        $user = $this->user($request);

        if ($this->twoFactor->has($user, TwoFactorMethodType::Totp)) {
            return redirect()->route('account.security.index')->with('warning', 'Die Authenticator-App ist bereits eingerichtet.');
        }

        $method = $this->twoFactor->pendingTotp($user) ?? $this->twoFactor->beginTotp($user);
        $secret = (string) $method->secret;

        return view('account.security.totp', [
            'secret' => Totp::format($secret),
            'uri' => Totp::uri($secret, $user->email, 'VDBS Portal'),
        ]);
    }

    public function totpStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:16']], [
            'code.required' => 'Bitte geben Sie den Code aus Ihrer App ein.',
        ]);

        $codes = $this->twoFactor->confirmTotp($this->user($request), $data['code']);

        if ($codes === null) {
            return back()->withErrors(['code' => 'Der Code ist nicht gültig. Prüfen Sie die Uhrzeit Ihres Geräts und versuchen Sie es mit dem aktuellen Code erneut.']);
        }

        AuditLog::record(AuditEvent::TwoFactorEnabled, $this->user($request), $this->user($request), ['methode' => 'totp']);

        return $this->afterActivation($codes, 'Die Authenticator-App ist eingerichtet.');
    }

    /** Einrichtung des E-Mail-Codes: Code senden, Eingabe abfragen. */
    public function emailCreate(Request $request): View|RedirectResponse
    {
        $user = $this->user($request);

        if ($this->twoFactor->has($user, TwoFactorMethodType::Email)) {
            return redirect()->route('account.security.index')->with('warning', 'Der E-Mail-Code ist bereits eingerichtet.');
        }

        $this->emailChallenges->send($user);

        return view('account.security.email', [
            'email' => $user->email,
            'resendIn' => $this->emailChallenges->secondsUntilResend($user),
        ]);
    }

    public function emailResend(Request $request): RedirectResponse
    {
        $sent = $this->emailChallenges->send($this->user($request));

        return redirect()
            ->route('account.security.email')
            ->with($sent ? 'success' : 'warning', $sent ? 'Wir haben Ihnen einen neuen Code gesendet.' : 'Bitte warten Sie kurz, bevor Sie einen weiteren Code anfordern.');
    }

    public function emailStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:16']], [
            'code.required' => 'Bitte geben Sie den Code aus der E-Mail ein.',
        ]);

        $codes = $this->twoFactor->confirmEmail($this->user($request), $data['code']);

        if ($codes === null) {
            return back()->withErrors(['code' => 'Der Code ist nicht gültig oder abgelaufen.']);
        }

        AuditLog::record(AuditEvent::TwoFactorEnabled, $this->user($request), $this->user($request), ['methode' => 'email']);

        return $this->afterActivation($codes, 'Der E-Mail-Code ist eingerichtet.');
    }

    /** Entfernt eine Methode (Passwort erforderlich). */
    public function destroy(Request $request, string $type): RedirectResponse
    {
        $method = TwoFactorMethodType::tryFrom($type) ?? abort(404);

        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
            'password.current_password' => 'Das Passwort ist nicht korrekt.',
        ]);

        $others = $this->twoFactor->activeMethods($this->user($request))->filter(fn ($active): bool => $active->type !== $method);

        if ($this->policy->isRequired($this->user($request)) && $others->isEmpty()) {
            return redirect()->route('account.security.index')->with('warning', 'Für Ihre Rolle ist die Zwei-Faktor-Anmeldung Pflicht. Richten Sie zuerst eine andere Methode ein, bevor Sie diese entfernen.');
        }

        $this->twoFactor->disable($this->user($request), $method);
        AuditLog::record(AuditEvent::TwoFactorDisabled, $this->user($request), $this->user($request), ['methode' => $method->value]);

        return redirect()->route('account.security.index')->with('success', 'Die Methode wurde entfernt.');
    }

    /** Einmalige Anzeige der frisch erzeugten Wiederherstellungscodes. */
    public function recoveryShow(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('recovery_codes');

        if (! is_array($codes) || $codes === []) {
            return redirect()->route('account.security.index');
        }

        return view('account.security.recovery-codes', ['codes' => $codes]);
    }

    /** Ersetzt alle unbenutzten Wiederherstellungscodes (Passwort erforderlich). */
    public function recoveryStore(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
            'password.current_password' => 'Das Passwort ist nicht korrekt.',
        ]);

        $user = $this->user($request);

        if (! $this->twoFactor->isEnabled($user)) {
            return redirect()->route('account.security.index')->with('warning', 'Richten Sie zuerst eine Methode ein.');
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);
        AuditLog::record(AuditEvent::RecoveryCodesRenewed, $user, $user);

        return redirect()->route('account.security.recovery')->with('recovery_codes', $codes);
    }

    /** @param  list<string>  $codes */
    private function afterActivation(array $codes, string $message): RedirectResponse
    {
        if ($codes === []) {
            return redirect()->route('account.security.index')->with('success', $message);
        }

        return redirect()->route('account.security.recovery')->with('recovery_codes', $codes)->with('success', $message);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

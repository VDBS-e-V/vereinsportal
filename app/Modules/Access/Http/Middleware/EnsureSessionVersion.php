<?php

namespace App\Modules\Access\Http\Middleware;

use App\Modules\Access\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Beendet Sitzungen, deren Version nicht mehr zum Konto passt (users.session_version).
 *
 * Wer das Passwort ändert oder zurücksetzt, die E-Mail-Adresse wechselt oder „alle anderen Sitzungen abmelden“ wählt,
 * erhöht die Version; alle Sitzungen mit der alten Version sind damit beim nächsten Aufruf abgemeldet, unabhängig vom
 * Sitzungs-Treiber. Sitzungen ohne gemerkte Version (z. B. von vor dieser Prüfung) übernehmen die aktuelle.
 */
class EnsureSessionVersion
{
    public const KEY = 'session_version';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $request->hasSession()) {
            return $next($request);
        }

        $remembered = $request->session()->get(self::KEY);

        if ($remembered === null) {
            $request->session()->put(self::KEY, (int) $user->session_version);

            return $next($request);
        }

        if ((int) $remembered !== (int) $user->session_version) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('warning', 'Ihre Sitzung wurde beendet, weil sich Ihre Zugangsdaten geändert haben. Bitte melden Sie sich erneut an.');
        }

        return $next($request);
    }
}

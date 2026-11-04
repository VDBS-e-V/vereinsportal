<?php

namespace App\Modules\Contacts\Http\Middleware;

use App\Modules\Contacts\Models\AccessCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lässt nur Personen mit gültigem persönlichem Zugang durch. Die Sitzung endet nach 30 Minuten ohne Aktivität oder sobald der Zugang gesperrt wird.
 * Sie gilt nur für die Seiten unter /zugang, nie für Bereiche des Portals.
 */
final class EnsureContactAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $state = $request->session()->get('contact_access');
        $idle = max(1, (int) config('portal.contacts.idle_minutes', 30)) * 60;

        if (! is_array($state) || ! isset($state['credential'], $state['at']) || time() - (int) $state['at'] > $idle) {
            $request->session()->forget('contact_access');

            return redirect()->route('access.code')->with('warning', 'Bitte melden Sie sich mit Ihrem Zugang an.');
        }

        $credential = AccessCredential::query()->valid()->with('contact')->find((int) $state['credential']);

        if ($credential === null || $credential->contact === null) {
            $request->session()->forget('contact_access');

            return redirect()->route('access.code')->with('warning', 'Ihr Zugang gilt nicht mehr. Fordern Sie einen neuen an.');
        }

        $request->session()->put('contact_access.at', time());
        $request->attributes->set('contact', $credential->contact);

        return $next($request);
    }
}

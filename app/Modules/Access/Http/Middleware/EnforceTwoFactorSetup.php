<?php

namespace App\Modules\Access\Http\Middleware;

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\TwoFactorPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Erzwingt die Einrichtung der Zwei-Faktor-Anmeldung für Rollen mit Pflicht (TwoFactorPolicy). Wer noch keine Methode hat,
 * erreicht nur noch die Sicherheitsseiten (Konto > Sicherheit) und die Abmeldung; alle anderen Seiten hinter der Anmeldung
 * leiten dorthin um. Öffentliche Seiten bleiben erreichbar.
 */
class EnforceTwoFactorSetup
{
    public function __construct(private readonly TwoFactorPolicy $policy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->policy->mustSetUp($user)) {
            return $next($request);
        }

        $route = $request->route();
        $name = $route?->getName();

        if ($route === null || $name === null || ! in_array('auth', $route->gatherMiddleware(), true) || Str::is(['account.security.*', 'logout'], $name)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Für Ihre Rolle ist die Zwei-Faktor-Anmeldung Pflicht.');
        }

        return redirect()->route('account.security.index')
            ->with('warning', 'Für Ihre Rolle ist die Zwei-Faktor-Anmeldung Pflicht. Bitte richten Sie zuerst eine Methode ein.');
    }
}

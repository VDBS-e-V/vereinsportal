<?php

namespace App\Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Begrenzt Aufrufe der Einrichtung (30 je Minute und Adresse). Bewusst ohne Sitzung, ohne Konto-Abfrage und mit dem Datei-Zwischenspeicher:
 * Die Einrichtung muss auch laufen, wenn es noch keinen APP_KEY und noch keine Datenbanktabellen gibt (die normale Begrenzung fragt das Konto ab
 * und nutzt den Zwischenspeicher der Datenbank).
 */
final class SetupThrottle
{
    public const MAX = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $limiter = new RateLimiter(Cache::store(app()->environment('testing') ? 'array' : 'file'));
        $key = 'setup:'.sha1((string) $request->ip());

        if ($limiter->tooManyAttempts($key, self::MAX)) {
            abort(429, 'Zu viele Aufrufe. Bitte warten Sie eine Minute.');
        }

        $limiter->hit($key, 60);

        return $next($request);
    }
}

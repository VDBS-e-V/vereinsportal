<?php

namespace App\Http\Controllers;

/**
 * Ziel der Fallback-Route: Unbekannte Adressen laufen so durch die Web-Middleware (Sitzung, Anmeldung) und liefern dann die 404-Seite.
 * Bewusst eine Controller-Klasse und keine Closure, damit "php artisan route:cache" funktioniert.
 */
class NotFoundController extends Controller
{
    public function __invoke(): never
    {
        abort(404);
    }
}

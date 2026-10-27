<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
 * Die Middleware-Aliase fuer Administration (administration.access,
 * administration.capability) und Identity (identity.revalidate) sind
 * mit ihren Modulen entfernt worden und kommen zurueck, sobald
 * Access-Governance bzw. Access ihre HTTP-Schicht bekommen. Bis dahin
 * gibt es auch keinen Login, auf den Gaeste umgeleitet werden koennten.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/administration.php');
            require base_path('routes/design.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

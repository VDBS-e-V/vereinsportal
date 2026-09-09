<?php

use App\Modules\Administration\Http\Middleware\RequireAdministrationAccess;
use App\Modules\Identity\Http\Middleware\RevalidateAuthenticatedUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $middleware->alias([
            'administration.access' => RequireAdministrationAccess::class,
            'identity.revalidate' => RevalidateAuthenticatedUser::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request): string => route('my.login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

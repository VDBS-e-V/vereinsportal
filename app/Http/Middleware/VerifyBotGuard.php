<?php

namespace App\Http\Middleware;

use App\Support\BotGuard\BotGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Prüft bei öffentlichen Formularen den Schutz vor automatisierten Eingaben (siehe BotGuard). Alias: bot.guard */
class VerifyBotGuard
{
    public function __construct(private readonly BotGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->guard->enabled() || $request->isMethodSafe()) {
            return $next($request);
        }

        $reason = $this->guard->failure($request);

        if ($reason === null) {
            return $next($request);
        }

        // Nur der Grund und die Route, keine Eingaben und keine IP-Adresse.
        Log::notice('bot_guard.rejected', ['reason' => $reason, 'route' => $request->route()?->getName()]);

        return back()
            ->withInput($request->except([BotGuard::TOKEN_FIELD, BotGuard::PROOF_FIELD, BotGuard::HONEYPOT_FIELD]))
            ->with('bot_guard_failed', true);
    }
}

<?php

namespace App\Modules\Sso\Http\Middleware;

use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Sso\Models\OAuthClientSetting;
use App\Modules\Sso\Services\OAuthClients;
use App\Modules\Sso\Services\OidcClaims;
use App\Modules\Sso\Support\IdTokenResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vor der Anmeldung bei einem anderen System (Adresse /oauth/authorize): nur aktive Konten mit einer erlaubten Rolle kommen durch.
 * Merkt sich außerdem den „nonce“ des Systems für das ID-Token.
 */
class GuardAuthorization
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('oauth/authorize')) {
            return $next($request);
        }

        $user = $request->user();
        $clientId = (string) $request->input('client_id', '');

        if ($user === null || $clientId === '') {
            return $next($request);
        }

        if (! OidcClaims::usable($user)) {
            return $this->forbidden($request);
        }

        $setting = OAuthClientSetting::query()->where('client_id', $clientId)->first();
        $allowed = (array) ($setting?->allowed_roles ?? []);

        if ($setting !== null && $allowed !== [] && array_intersect($allowed, Capabilities::activeRoleKeys($user)) === []) {
            return $this->forbidden($request);
        }

        if ($request->isMethod('get') && is_string($request->query('nonce')) && $request->query('nonce') !== '') {
            Cache::put(IdTokenResponse::nonceKey($clientId, $user->id), mb_substr((string) $request->query('nonce'), 0, 255), now()->addMinutes(10));
        }

        return $next($request);
    }

    private function forbidden(Request $request): Response
    {
        $client = app(OAuthClients::class)->find((string) $request->input('client_id'));

        return response()->view('sso.forbidden', ['name' => $client?->name], 403);
    }
}

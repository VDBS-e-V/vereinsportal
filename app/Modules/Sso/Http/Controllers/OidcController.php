<?php

namespace App\Modules\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sso\Services\OAuthClients;
use App\Modules\Sso\Services\OAuthKeys;
use App\Modules\Sso\Services\OidcClaims;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Öffentliche Schnittstellen für angebundene Systeme: Discovery, Schlüssel, Benutzerangaben, Abmeldung. */
class OidcController extends Controller
{
    public function __construct(private readonly OAuthKeys $keys, private readonly OAuthClients $clients, private readonly OidcClaims $claims) {}

    public function discovery(): JsonResponse
    {
        $issuer = rtrim(url('/'), '/');

        return response()->json([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer.'/oauth/authorize',
            'token_endpoint' => $issuer.'/oauth/token',
            'userinfo_endpoint' => $issuer.'/oauth/userinfo',
            'jwks_uri' => $issuer.'/oauth/jwks',
            'end_session_endpoint' => $issuer.'/oauth/logout',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => array_keys(OidcClaims::SCOPES),
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic', 'none'],
            'code_challenge_methods_supported' => ['S256'],
            'claims_supported' => ['sub', 'iss', 'aud', 'exp', 'iat', 'auth_time', 'nonce', 'name', 'given_name', 'family_name', 'preferred_username', 'email', 'email_verified', 'roles', 'groups'],
        ]);
    }

    public function jwks(): JsonResponse
    {
        $jwk = $this->keys->jwk();

        return response()->json(['keys' => $jwk ? [$jwk] : []]);
    }

    public function userinfo(Request $request): JsonResponse
    {
        $user = $request->user('api');

        if ($user === null || ! OidcClaims::usable($user)) {
            return response()->json(['error' => 'invalid_token'], 401, ['WWW-Authenticate' => 'Bearer error="invalid_token"']);
        }

        $scopes = array_values(array_filter(array_keys(OidcClaims::SCOPES), fn (string $scope): bool => $user->tokenCan($scope)));

        return response()->json($this->claims->forUser($user, $scopes))->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $ids = DB::table('oauth_access_tokens')->where('user_id', $user->getKey())->pluck('id')->all();

            if ($ids !== []) {
                DB::table('oauth_access_tokens')->whereIn('id', $ids)->update(['revoked' => true]);
                DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $ids)->update(['revoked' => true]);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $target = (string) $request->input('post_logout_redirect_uri', '');

        return redirect()->to($this->allowedTarget($target) ? $target : route('home'));
    }

    /** Nur Adressen, deren Herkunft zu einer eingetragenen Umleitungsadresse einer aktiven Anbindung gehört. */
    private function allowedTarget(string $target): bool
    {
        $origin = $this->origin($target);

        if ($origin === null) {
            return false;
        }

        foreach ($this->clients->all() as $client) {
            if ($client->revoked) {
                continue;
            }

            foreach ($client->redirect_uris as $uri) {
                if ($this->origin((string) $uri) === $origin) {
                    return true;
                }
            }
        }

        return false;
    }

    private function origin(string $uri): ?string
    {
        $parts = parse_url($uri);

        if (! $parts || empty($parts['scheme']) || empty($parts['host']) || ! in_array(strtolower($parts['scheme']), ['https', 'http'], true)) {
            return null;
        }

        return strtolower($parts['scheme']).'://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}

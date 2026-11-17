<?php

namespace App\Modules\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\ProfilePhotos;
use App\Modules\Access\Services\Profiles;
use App\Modules\Sso\Models\OAuthClientSetting;
use App\Modules\Sso\Services\ConnectedServices;
use App\Modules\Sso\Services\OAuthClients;
use App\Modules\Sso\Services\OAuthKeys;
use App\Modules\Sso\Services\OidcClaims;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Öffentliche Schnittstellen für angebundene Systeme: Discovery, Schlüssel, Benutzerangaben, Abmeldung. */
class OidcController extends Controller
{
    public function __construct(private readonly OAuthKeys $keys, private readonly OAuthClients $clients, private readonly OidcClaims $claims, private readonly ConnectedServices $services) {}

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
            'frontchannel_logout_supported' => true,
            'frontchannel_logout_session_supported' => false,
            'claims_supported' => ['sub', 'iss', 'aud', 'exp', 'iat', 'auth_time', 'nonce', 'name', 'given_name', 'family_name', 'preferred_username', 'email', 'email_verified', 'roles', 'groups', 'headline', 'bio', 'picture'],
        ]);
    }

    /** Logo eines angebundenen Systems für die Anmelde- und Zustimmungsseite (öffentlich, da es vor der Anmeldung erscheint). */
    public function logo(string $client): StreamedResponse
    {
        $path = OAuthClientSetting::query()->where('client_id', $client)->value('logo_path');

        abort_unless(filled($path) && Storage::disk('local')->exists((string) $path), 404);

        return Storage::disk('local')->response((string) $path, null, [
            'Content-Type' => ['png' => 'image/png', 'webp' => 'image/webp'][pathinfo((string) $path, PATHINFO_EXTENSION)] ?? 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Profilbild für ein angebundenes System: nur mit gültiger Signatur und nur, wenn die Person es für Angemeldete freigegeben hat. */
    public function photo(User $user): StreamedResponse
    {
        abort_unless(filled($user->avatar_path) && isset(app(Profiles::class)->preview($user, true)['photo']), 404);
        abort_unless(Storage::disk(ProfilePhotos::DISK)->exists($user->avatar_path), 404);

        return Storage::disk(ProfilePhotos::DISK)->response($user->avatar_path, null, [
            'Content-Type' => ['png' => 'image/png', 'webp' => 'image/webp'][pathinfo($user->avatar_path, PATHINFO_EXTENSION)] ?? 'image/jpeg',
            'Cache-Control' => 'private, max-age=600',
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

    /**
     * Abmeldung, die ein angebundenes System anstößt (end_session). Das System hat sich selbst schon abgemeldet; das Portal zeigt, wo die Person noch
     * angemeldet ist, und fragt, ob sie sich dort auch abmelden möchte. Ohne Portalsitzung geht es sofort zurück.
     */
    public function logout(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        $requested = (string) $request->input('post_logout_redirect_uri', '');
        $target = $this->allowedTarget($requested) ? $requested : null;

        if ($user === null) {
            return redirect()->to($target ?? route('home'));
        }

        $initiator = $this->initiator($request, $target);

        if ($initiator !== null) {
            $this->services->revokeFor($user, (string) $initiator->getKey());
        }

        $request->session()->put('sso_logout', ['target' => $target, 'initiator' => $initiator?->getKey()]);

        return view('sso.logout', [
            'initiator' => $initiator?->name,
            'others' => $this->services->withLogout($user, $initiator !== null ? (string) $initiator->getKey() : null),
            'stay' => $target ?? route('home'),
        ]);
    }

    /** „Ja, abmelden“: Portalsitzung beenden, alle Tokens widerrufen und den Browser die Abmelde-Adressen der anderen Systeme aufrufen lassen. */
    public function logoutPerform(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        $pending = (array) $request->session()->get('sso_logout', []);
        $target = is_string($pending['target'] ?? null) ? $pending['target'] : null;

        if ($user === null) {
            return redirect()->to($target ?? route('login'));
        }

        $others = $this->services->withLogout($user, isset($pending['initiator']) ? (string) $pending['initiator'] : null);
        $this->services->revokeAll($user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return view('sso.logout-done', ['services' => $others, 'next' => $target ?? route('login')]);
    }

    /** Welches System meldet sich ab? Aus dem id_token_hint (Empfänger) oder, ohne den, aus der Herkunft der Rücksprung-Adresse. */
    private function initiator(Request $request, ?string $target): ?\Laravel\Passport\Client
    {
        $hint = (string) $request->input('id_token_hint', '');
        $audience = $hint !== '' ? $this->audience($hint) : null;
        $client = $audience !== null ? $this->clients->find($audience) : null;

        if ($client !== null && ! $client->revoked) {
            return $client;
        }

        $origin = $target !== null ? $this->origin($target) : null;

        if ($origin === null) {
            return null;
        }

        foreach ($this->clients->all() as $candidate) {
            if ($candidate->revoked) {
                continue;
            }

            foreach ($candidate->redirect_uris as $uri) {
                if ($this->origin((string) $uri) === $origin) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function audience(string $jwt): ?string
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = strtr($parts[1], '-_', '+/');
        $data = json_decode((string) base64_decode($payload.str_repeat('=', (4 - strlen($payload) % 4) % 4), true), true);
        $aud = is_array($data) ? ($data['aud'] ?? null) : null;

        return is_array($aud) ? (isset($aud[0]) ? (string) $aud[0] : null) : (is_string($aud) ? $aud : null);
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

            foreach ((array) $client->getAttribute('redirect_uris') as $uri) {
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

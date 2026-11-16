<?php

namespace App\Modules\Sso\Services;

use App\Modules\Access\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Die angebundenen Systeme, bei denen ein Konto über das Portal angemeldet ist, und die Abmeldung dort (Front-Channel Logout).
 * Nur Systeme mit eingetragener Abmelde-Adresse lassen sich vom Portal aus abmelden; die Tokens aller Systeme werden beim Abmelden widerrufen.
 */
final class ConnectedServices
{
    public function __construct(private readonly OAuthClients $clients) {}

    /**
     * Systeme mit gültiger Anmeldung dieses Kontos und Abmelde-Adresse.
     *
     * @return list<array{id: string, name: string, url: string}>
     */
    public function withLogout(User $user, ?string $exceptClientId = null): array
    {
        $ids = DB::table('oauth_access_tokens')
            ->where('user_id', $user->getKey())->where('revoked', false)->where('expires_at', '>', now())
            ->distinct()->pluck('client_id')->map(fn ($id): string => (string) $id)->all();

        $services = [];

        foreach ($this->clients->all() as $client) {
            $id = (string) $client->getKey();

            if ($client->revoked || $id === $exceptClientId || ! in_array($id, $ids, true)) {
                continue;
            }

            $uri = $this->clients->settings($client)->frontchannel_logout_uri;

            if (blank($uri)) {
                continue;
            }

            $services[] = ['id' => $id, 'name' => (string) $client->name, 'url' => $this->withIssuer((string) $uri)];
        }

        return $services;
    }

    /** Alle Tokens des Kontos widerrufen (bei Abmeldung vom Portal). */
    public function revokeAll(User $user): void
    {
        $this->revoke(DB::table('oauth_access_tokens')->where('user_id', $user->getKey()));
    }

    /** Die Tokens eines Systems widerrufen (das System hat sich selbst abgemeldet). */
    public function revokeFor(User $user, string $clientId): void
    {
        $this->revoke(DB::table('oauth_access_tokens')->where('user_id', $user->getKey())->where('client_id', $clientId));
    }

    /** @param  \Illuminate\Database\Query\Builder  $tokens */
    private function revoke($tokens): void
    {
        $ids = $tokens->pluck('id')->all();

        if ($ids === []) {
            return;
        }

        DB::table('oauth_access_tokens')->whereIn('id', $ids)->update(['revoked' => true]);
        DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $ids)->update(['revoked' => true]);
    }

    /** Die Adresse des Portals als „iss“ anhängen, wie es OpenID Connect Front-Channel Logout vorsieht. */
    private function withIssuer(string $uri): string
    {
        return $uri.(str_contains($uri, '?') ? '&' : '?').http_build_query(['iss' => rtrim(url('/'), '/')]);
    }
}

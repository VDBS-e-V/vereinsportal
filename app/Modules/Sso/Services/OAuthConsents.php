<?php

namespace App\Modules\Sso\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Sso\Models\OAuthConsent;
use Illuminate\Support\Collection;

/**
 * Zustimmungen zur Weitergabe von Daten an angebundene Systeme: einmal je System, bis zum Widerruf. Verlangt ein System später weitere Bereiche
 * (Scopes), die noch nicht zugestimmt sind, fragt das Portal erneut. Der Widerruf löscht die Zustimmung und widerruft alle Tokens des Systems.
 */
final class OAuthConsents
{
    public function __construct(private readonly OAuthClients $clients, private readonly ConnectedServices $services) {}

    /**
     * Deckt die gespeicherte Zustimmung alle verlangten Bereiche ab?
     *
     * @param  list<string>  $scopes
     */
    public function covers(mixed $user, string $clientId, array $scopes): bool
    {
        $consent = OAuthConsent::query()->where('user_id', $user->getAuthIdentifier())->where('client_id', $clientId)->first();

        return $consent !== null && array_diff($scopes, (array) $consent->scopes) === [];
    }

    /**
     * Zustimmung speichern oder um weitere Bereiche ergänzen.
     *
     * @param  list<string>  $scopes
     */
    public function grant(int|string $userId, string $clientId, array $scopes): void
    {
        $consent = OAuthConsent::query()->firstOrNew(['user_id' => $userId, 'client_id' => $clientId]);
        $isNew = ! $consent->exists;

        $consent->scopes = array_values(array_unique([...(array) $consent->scopes, ...$scopes]));
        $consent->granted_at = $consent->granted_at ?? now();
        $consent->save();

        if ($isNew) {
            $user = User::query()->find($userId);
            $client = $this->clients->find($clientId);
            AuditLog::record(AuditEvent::OauthConsentGiven, $user, null, ['anbindung' => (string) $client?->name]);
        }
    }

    /** Zustimmung widerrufen: Eintrag löschen, Tokens des Systems widerrufen. */
    public function withdraw(User $user, string $clientId): void
    {
        $had = OAuthConsent::query()->where('user_id', $user->id)->where('client_id', $clientId)->delete() > 0;
        $this->services->revokeFor($user, $clientId);

        if ($had) {
            AuditLog::record(AuditEvent::OauthConsentWithdrawn, $user, null, ['anbindung' => (string) $this->clients->find($clientId)?->name]);
        }
    }

    /**
     * Die Zustimmungen eines Kontos mit System und Bereichen (nur vorhandene, nicht gesperrte Systeme).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forUser(User $user): Collection
    {
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = OAuthConsent::query()->where('user_id', $user->id)->orderByDesc('granted_at')->get()
            ->map(function (OAuthConsent $consent): ?array {
                $client = $this->clients->find($consent->client_id);

                if ($client === null || $client->revoked) {
                    return null;
                }

                return ['client_id' => $consent->client_id, 'name' => (string) $client->name, 'description' => $this->clients->settings($client)->description, 'scopes' => array_values(array_map('strval', (array) $consent->scopes)), 'granted_at' => $consent->granted_at];
            })->filter()->values();

        return $rows;
    }
}

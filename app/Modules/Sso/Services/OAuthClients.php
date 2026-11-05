<?php

namespace App\Modules\Sso\Services;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Sso\Models\OAuthClientSetting;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

/** Anbindungen (OAuth-Clients) anlegen, ändern, sperren, löschen. Geheimnisse sind nur direkt nach dem Anlegen oder Erneuern lesbar, gespeichert wird nur der Hash. */
final class OAuthClients
{
    private const MAX_REDIRECTS = 10;

    public function __construct(private readonly ClientRepository $clients) {}

    /** Rollen, die sich standardmäßig anmelden dürfen (Mitarbeitende, Koordination, Bildungsreferenten, Geschäftsstelle, Vorstand). @return list<string> */
    public static function defaultRoles(): array
    {
        return [RoleKey::Administration->value, RoleKey::AdministrationStaff->value, RoleKey::Coordination->value, RoleKey::EducationCoordination->value, RoleKey::BoardMember->value];
    }

    /** @return array<string, string> Rollenschlüssel => Bezeichnung */
    public static function roleOptions(): array
    {
        return collect(RoleKey::cases())->reject(fn (RoleKey $role): bool => $role === RoleKey::Guest)->mapWithKeys(fn (RoleKey $role): array => [$role->value => $role->label()])->all();
    }

    /** @return Collection<int, Client> */
    public function all(): Collection
    {
        return Passport::client()->newQuery()->orderBy('name')->get()->filter(fn (Client $client): bool => $client->hasGrantType('authorization_code'))->values();
    }

    public function find(string $id): ?Client
    {
        return $this->clients->find($id);
    }

    public function settings(Client $client): OAuthClientSetting
    {
        return OAuthClientSetting::query()->firstOrNew(['client_id' => (string) $client->getKey()], ['show_consent' => true, 'allowed_roles' => self::defaultRoles()]);
    }

    /**
     * @param  array<string, mixed>  $input  name, redirect_uris (Text), confidential, roles, description, contact, show_consent
     *
     * @throws DomainException
     */
    public function create(array $input, User $by): Client
    {
        [$name, $uris, $roles] = $this->checked($input);

        $client = $this->clients->createAuthorizationCodeGrantClient($name, $uris, ! array_key_exists('confidential', $input) || (bool) $input['confidential']);

        OAuthClientSetting::query()->create($this->settingData($client, $input, $roles) + ['created_by_user_id' => $by->id]);
        AuditLog::record(AuditEvent::OauthClientCreated, $by, null, ['anbindung' => $name]);

        return $client;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws DomainException
     */
    public function update(Client $client, array $input, User $by): Client
    {
        [$name, $uris, $roles] = $this->checked($input);

        $this->clients->update($client, $name, $uris);
        $setting = $this->settings($client);
        $setting->fill($this->settingData($client, $input, $roles))->save();
        AuditLog::record(AuditEvent::OauthClientChanged, $by, null, ['anbindung' => $name, 'aktion' => 'geändert']);

        return $client->refresh();
    }

    /** Erzeugt ein neues Geheimnis und liefert es einmalig im Klartext. */
    public function renewSecret(Client $client, User $by): string
    {
        $plain = Str::random(40);
        $client->forceFill(['secret' => $plain])->save();
        AuditLog::record(AuditEvent::OauthClientChanged, $by, null, ['anbindung' => (string) $client->name, 'aktion' => 'Geheimnis erneuert']);

        return $plain;
    }

    /** Sperrt (widerruft alle Tokens) oder entsperrt eine Anbindung. */
    public function setBlocked(Client $client, bool $blocked, User $by): void
    {
        $client->forceFill(['revoked' => $blocked])->save();

        if ($blocked) {
            $this->revokeTokens($client);
        }

        AuditLog::record(AuditEvent::OauthClientChanged, $by, null, ['anbindung' => (string) $client->name, 'aktion' => $blocked ? 'gesperrt' : 'entsperrt']);
    }

    public function delete(Client $client, User $by): void
    {
        $name = (string) $client->name;
        $id = (string) $client->getKey();

        DB::transaction(function () use ($client, $id): void {
            $this->revokeTokens($client);
            $this->clients->delete($client);
            $client->delete();
            OAuthClientSetting::query()->where('client_id', $id)->delete();
        });

        AuditLog::record(AuditEvent::OauthClientDeleted, $by, null, ['anbindung' => $name]);
    }

    public function revokeTokens(Client $client): void
    {
        $ids = $client->tokens()->pluck('id')->all();

        if ($ids === []) {
            return;
        }

        DB::table('oauth_access_tokens')->whereIn('id', $ids)->update(['revoked' => true]);
        DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $ids)->update(['revoked' => true]);
    }

    /**
     * Prüft Name, Umleitungsadressen und Rollen.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: string, 1: list<string>, 2: list<string>}
     *
     * @throws DomainException
     */
    private function checked(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            throw new DomainException('Bitte geben Sie der Anbindung einen Namen.');
        }

        $uris = collect(preg_split('/\R/', (string) ($input['redirect_uris'] ?? '')) ?: [])->map(fn ($line) => trim((string) $line))->filter()->unique()->values()->all();

        if ($uris === []) {
            throw new DomainException('Bitte geben Sie mindestens eine Umleitungsadresse an.');
        }

        if (count($uris) > self::MAX_REDIRECTS) {
            throw new DomainException('Es sind höchstens '.self::MAX_REDIRECTS.' Umleitungsadressen möglich.');
        }

        foreach ($uris as $uri) {
            $this->assertRedirect($uri);
        }

        $roles = array_values(array_intersect(array_map('strval', (array) ($input['roles'] ?? [])), array_keys(self::roleOptions())));

        if ($roles === []) {
            throw new DomainException('Bitte wählen Sie mindestens eine Rolle, die sich anmelden darf.');
        }

        return [Str::limit($name, 100, ''), $uris, $roles];
    }

    /** @throws DomainException */
    private function assertRedirect(string $uri): void
    {
        $parts = parse_url($uri);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $local = in_array($host, ['localhost', '127.0.0.1'], true) && app()->environment(['local', 'testing']);

        if ($parts === false || $host === '' || isset($parts['fragment']) || str_contains($uri, '*') || ! ($scheme === 'https' || ($scheme === 'http' && $local))) {
            throw new DomainException('Die Umleitungsadresse „'.Str::limit($uri, 80).'“ ist nicht erlaubt. Sie muss mit https:// beginnen, darf kein # und keinen Platzhalter enthalten.');
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    private function settingData(Client $client, array $input, array $roles): array
    {
        return [
            'client_id' => (string) $client->getKey(),
            'allowed_roles' => $roles,
            'description' => Str::limit(trim((string) ($input['description'] ?? '')), 1000, '') ?: null,
            'contact' => Str::limit(trim((string) ($input['contact'] ?? '')), 190, '') ?: null,
            'show_consent' => ! array_key_exists('show_consent', $input) || (bool) $input['show_consent'],
        ];
    }
}

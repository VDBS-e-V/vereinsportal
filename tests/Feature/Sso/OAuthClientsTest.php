<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Modules\Sso\Models\OAuthClientSetting;
use App\Modules\Sso\Services\OAuthClients;
use App\Modules\Sso\Services\OAuthKeys;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function ocUser(?RoleKey $role = RoleKey::Administration): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'password' => 'geheim-Passwort-123']);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function ocData(array $over = []): array
{
    return array_merge(['name' => 'Nextcloud', 'redirect_uris' => "https://cloud.example.org/apps/user_oidc/code", 'confidential' => 1, 'roles' => ['coordination', 'administration_staff'], 'description' => 'Dateien', 'contact' => 'it@example.org', 'show_consent' => 1], $over);
}

it('gives every account a stable public id', function () {
    $user = ocUser(null);

    expect($user->public_uuid)->toMatch('/^[0-9a-f\-]{36}$/')->and(ocUser(null)->public_uuid)->not->toBe($user->public_uuid);
});

it('keeps the connections for the administration only', function () {
    foreach ([RoleKey::BoardMember, RoleKey::AdministrationStaff, RoleKey::Coordination, null] as $role) {
        $this->actingAs(ocUser($role))->get(route('admin.oauth.index'))->assertForbidden();
    }

    $this->actingAs(ocUser())->get(route('admin.oauth.index'))->assertOk()->assertSee('Anmeldeschlüssel');
});

it('creates a connection and shows the secret once', function () {
    $admin = ocUser();

    $response = $this->actingAs($admin)->post(route('admin.oauth.store'), ocData())->assertRedirect();
    $client = Client::query()->where('name', 'Nextcloud')->firstOrFail();

    expect($client->redirect_uris)->toBe(['https://cloud.example.org/apps/user_oidc/code'])
        ->and($client->confidential())->toBeTrue()
        ->and($client->hasGrantType('authorization_code'))->toBeTrue()
        ->and(OAuthClientSetting::query()->where('client_id', (string) $client->getKey())->first()->allowed_roles)->toBe(['coordination', 'administration_staff']);

    $secret = session('oauth_secret');
    expect($secret)->toBeString()->and(strlen($secret))->toBeGreaterThan(20)
        ->and(DB::table('oauth_clients')->where('id', $client->getKey())->value('secret'))->not->toBe($secret);

    $this->get($response->headers->get('Location'))->assertOk()->assertSee($secret);
    $this->get(route('admin.oauth.show', $client->getKey()))->assertOk()->assertDontSee($secret);
    expect(AuditEntry::query()->where('event', 'oauth.client_created')->count())->toBe(1);
});

it('validates names, redirect addresses and roles', function () {
    $admin = ocUser();

    foreach ([['name' => ''], ['redirect_uris' => ''], ['redirect_uris' => 'http://cloud.example.org/cb'], ['redirect_uris' => 'https://cloud.example.org/*'], ['redirect_uris' => 'https://cloud.example.org/cb#x'], ['redirect_uris' => 'javascript:alert(1)'], ['roles' => []]] as $bad) {
        $this->actingAs($admin)->post(route('admin.oauth.store'), ocData($bad))->assertSessionHasErrors('form');
    }

    $this->actingAs($admin)->post(route('admin.oauth.store'), ocData(['redirect_uris' => implode("\n", array_map(fn ($i) => "https://c$i.example.org/cb", range(1, 11)))]))->assertSessionHasErrors('form');
    expect(Client::query()->count())->toBe(0);
});

it('allows local http addresses only in local use', function () {
    $this->actingAs(ocUser())->post(route('admin.oauth.store'), ocData(['redirect_uris' => 'http://localhost:8080/cb']))->assertSessionHasNoErrors();
    expect(Client::query()->count())->toBe(1);
});

it('edits a connection and keeps unchecked consent off', function () {
    $admin = ocUser();
    $client = app(OAuthClients::class)->create(ocData(), $admin);

    $this->actingAs($admin)->put(route('admin.oauth.update', $client->getKey()), ocData(['name' => 'Cloud', 'roles' => ['board_member'], 'show_consent' => 0]))->assertRedirect();

    $setting = OAuthClientSetting::query()->where('client_id', (string) $client->getKey())->first();
    expect($client->fresh()->name)->toBe('Cloud')->and($setting->allowed_roles)->toBe(['board_member'])->and($setting->show_consent)->toBeFalse();
    $this->actingAs($admin)->get(route('admin.oauth.edit', $client->getKey()))->assertOk()->assertSee('Cloud');
});

it('renews the secret only with the password', function () {
    $admin = ocUser();
    $client = app(OAuthClients::class)->create(ocData(), $admin);
    $old = DB::table('oauth_clients')->where('id', $client->getKey())->value('secret');

    $this->actingAs($admin)->post(route('admin.oauth.secret', $client->getKey()), ['password' => 'falsch'])->assertSessionHasErrors('password');
    expect(DB::table('oauth_clients')->where('id', $client->getKey())->value('secret'))->toBe($old);

    $this->actingAs($admin)->post(route('admin.oauth.secret', $client->getKey()), ['password' => 'geheim-Passwort-123'])->assertRedirect();
    expect(DB::table('oauth_clients')->where('id', $client->getKey())->value('secret'))->not->toBe($old)->and(session('oauth_secret'))->toBeString();
});

it('blocks a connection, revokes its tokens and releases it again', function () {
    $admin = ocUser();
    $client = app(OAuthClients::class)->create(ocData(), $admin);
    DB::table('oauth_access_tokens')->insert(['id' => 'tok1', 'user_id' => $admin->id, 'client_id' => $client->getKey(), 'name' => null, 'scopes' => '[]', 'revoked' => false, 'created_at' => now(), 'updated_at' => now(), 'expires_at' => now()->addHour()]);

    $this->actingAs($admin)->post(route('admin.oauth.block', $client->getKey()), ['action' => 'block'])->assertRedirect();
    expect($client->fresh()->revoked)->toBeTrue()->and((bool) DB::table('oauth_access_tokens')->where('id', 'tok1')->value('revoked'))->toBeTrue();

    $this->actingAs($admin)->post(route('admin.oauth.block', $client->getKey()), ['action' => 'unblock'])->assertRedirect();
    expect($client->fresh()->revoked)->toBeFalse();
});

it('deletes a connection with its settings after the password', function () {
    $admin = ocUser();
    $client = app(OAuthClients::class)->create(ocData(), $admin);

    $this->actingAs($admin)->delete(route('admin.oauth.destroy', $client->getKey()), ['password' => 'falsch'])->assertSessionHasErrors('password');
    expect(Client::query()->count())->toBe(1);

    $this->actingAs($admin)->delete(route('admin.oauth.destroy', $client->getKey()), ['password' => 'geheim-Passwort-123'])->assertRedirect(route('admin.oauth.index'));
    expect(Client::query()->count())->toBe(0)->and(OAuthClientSetting::query()->count())->toBe(0)->and(AuditEntry::query()->where('event', 'oauth.client_deleted')->count())->toBe(1);
});

it('keeps secrets and addresses out of the log', function () {
    $admin = ocUser();
    app(OAuthClients::class)->create(ocData(), $admin);
    $entry = AuditEntry::query()->where('event', 'oauth.client_created')->firstOrFail();

    expect(json_encode($entry->context))->toContain('Nextcloud')->not->toContain('cloud.example.org');
});

it('creates and shows the keys', function () {
    $keys = app(OAuthKeys::class);
    $admin = ocUser();

    $this->actingAs($admin)->post(route('admin.oauth.keys.create'))->assertRedirect();
    $status = $keys->status();

    expect($status['exists'])->toBeTrue()->and($status['fingerprint'])->toMatch('/^([0-9A-F]{4}:){5}[0-9A-F]{4}$/');
    $this->actingAs($admin)->get(route('admin.oauth.index'))->assertOk()->assertSee($status['fingerprint'])->assertDontSee('BEGIN');

    $before = $status['fingerprint'];
    $this->actingAs($admin)->post(route('admin.oauth.keys.renew'), ['password' => 'falsch'])->assertSessionHasErrors('password');
    expect($keys->status()['fingerprint'])->toBe($before);

    $this->actingAs($admin)->post(route('admin.oauth.keys.renew'), ['password' => 'geheim-Passwort-123'])->assertRedirect();
    expect($keys->status()['fingerprint'])->not->toBe($before)->and(AuditEntry::query()->where('event', 'oauth.keys_renewed')->count())->toBe(1);
});

it('lists the connections page in the admin navigation', function () {
    $this->actingAs(ocUser())->get(route('admins.home'))->assertOk()->assertSee('Anbindungen');
});

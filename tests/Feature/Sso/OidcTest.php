<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use App\Modules\Sso\Services\OAuthClients;
use App\Modules\Sso\Services\OAuthKeys;
use App\Modules\Sso\Services\OidcClaims;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    app(OAuthKeys::class)->ensure();
});

function odUser(?RoleKey $role = RoleKey::Coordination, array $over = []): User
{
    $person = Person::factory()->create(['first_name' => 'Alex', 'last_name' => 'Muster']);
    $user = User::factory()->create(array_merge(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()], $over));

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function odClient(array $over = [])
{
    $admin = odUser(RoleKey::Administration);

    return app(OAuthClients::class)->create(array_merge(['name' => 'Nextcloud', 'redirect_uris' => 'https://cloud.example.org/cb', 'confidential' => true, 'roles' => ['coordination', 'board_member'], 'show_consent' => false], $over), $admin);
}

function odB64(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/** Führt Anmeldung und Token-Abruf durch und liefert Antwort der Token-Adresse. */
function odFlow($test, User $user, $client, string $scope = 'openid profile email roles groups', string $nonce = 'n-0S6_WzA2Mj'): array
{
    $verifier = str_repeat('a1B2c3D4e5', 6);
    $challenge = odB64(hash('sha256', $verifier, true));

    $auth = $test->actingAs($user)->get('/oauth/authorize?'.http_build_query(['client_id' => $client->getKey(), 'redirect_uri' => 'https://cloud.example.org/cb', 'response_type' => 'code', 'scope' => $scope, 'state' => 'xyz', 'nonce' => $nonce, 'code_challenge' => $challenge, 'code_challenge_method' => 'S256']));
    $auth->assertRedirect();
    parse_str((string) parse_url($auth->headers->get('Location'), PHP_URL_QUERY), $query);

    $token = $test->post('/oauth/token', ['grant_type' => 'authorization_code', 'client_id' => $client->getKey(), 'client_secret' => $client->plainSecret, 'redirect_uri' => 'https://cloud.example.org/cb', 'code' => $query['code'] ?? '', 'code_verifier' => $verifier]);

    return ['state' => $query['state'] ?? null, 'token' => $token];
}

it('publishes the discovery document with the required fields', function () {
    $json = $this->getJson('/.well-known/openid-configuration')->assertOk()->json();

    expect($json['issuer'])->toBe(rtrim(url('/'), '/'))
        ->and($json['authorization_endpoint'])->toEndWith('/oauth/authorize')
        ->and($json['token_endpoint'])->toEndWith('/oauth/token')
        ->and($json['userinfo_endpoint'])->toEndWith('/oauth/userinfo')
        ->and($json['jwks_uri'])->toEndWith('/oauth/jwks')
        ->and($json['id_token_signing_alg_values_supported'])->toBe(['RS256'])
        ->and($json['code_challenge_methods_supported'])->toBe(['S256'])
        ->and($json['scopes_supported'])->toContain('openid', 'groups')
        ->and($json['grant_types_supported'])->not->toContain('password', 'implicit');
});

it('publishes the public key with a key id and never the private key', function () {
    $response = $this->getJson('/oauth/jwks')->assertOk();
    $key = $response->json('keys.0');

    expect($key['kty'])->toBe('RSA')->and($key['alg'])->toBe('RS256')->and($key['kid'])->toBe(app(OAuthKeys::class)->keyId())->and($key)->toHaveKeys(['n', 'e'])->not->toHaveKey('d');
});

it('runs the whole login: code with PKCE, tokens, signed id token and userinfo', function () {
    $client = odClient();
    $user = odUser(RoleKey::Coordination);

    $flow = odFlow($this, $user, $client);
    expect($flow['state'])->toBe('xyz');

    $body = $flow['token']->assertOk()->json();
    expect($body)->toHaveKeys(['access_token', 'refresh_token', 'id_token']);

    [$h, $p, $s] = explode('.', $body['id_token']);
    $header = json_decode(base64_decode(strtr($h, '-_', '+/')), true);
    $claims = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
    $valid = openssl_verify($h.'.'.$p, base64_decode(strtr($s, '-_', '+/')), (string) file_get_contents(app(OAuthKeys::class)->publicPath()), OPENSSL_ALGO_SHA256);

    expect($valid)->toBe(1)
        ->and($header['alg'])->toBe('RS256')->and($header['kid'])->toBe(app(OAuthKeys::class)->keyId())
        ->and($claims['iss'])->toBe(rtrim(url('/'), '/'))
        ->and((array) $claims['aud'])->toBe([(string) $client->getKey()])
        ->and($claims['sub'])->toBe($user->public_uuid)
        ->and($claims['nonce'])->toBe('n-0S6_WzA2Mj')
        ->and($claims['email'])->toBe($user->email)->and($claims['email_verified'])->toBeTrue()
        ->and($claims['name'])->toBe('Alex Muster')
        ->and($claims['roles'])->toBe(['coordination'])
        ->and($claims['groups'])->toBe(['vdbs-koordination'])
        ->and($claims['exp'])->toBeGreaterThan(time());

    app('auth')->forgetGuards();
    $info = $this->withHeader('Authorization', 'Bearer '.$body['access_token'])->getJson('/oauth/userinfo')->assertOk()->json();
    expect($info['sub'])->toBe($user->public_uuid)->and($info['email'])->toBe($user->email)->and($info['groups'])->toBe(['vdbs-koordination']);
});

it('hands out only the claims of the granted scopes and never address, birthday or phone', function () {
    $client = odClient();
    $user = odUser(RoleKey::Coordination);

    $body = odFlow($this, $user, $client, 'openid email')['token']->assertOk()->json();
    $claims = json_decode(base64_decode(strtr(explode('.', $body['id_token'])[1], '-_', '+/')), true);

    expect($claims)->toHaveKey('email')->not->toHaveKeys(['name', 'roles', 'groups', 'street', 'address', 'birthdate', 'phone_number', 'birth_date', 'postal_code']);

    $full = odFlow($this, $user, $client)['token']->json();
    $all = json_decode(base64_decode(strtr(explode('.', $full['id_token'])[1], '-_', '+/')), true);
    expect(array_keys($all))->not->toContain('address', 'street', 'birthdate', 'phone_number', 'birth_date', 'postal_code', 'phone');
});

it('lets only the allowed roles log in', function () {
    $client = odClient();
    $verifier = str_repeat('x', 50);
    $params = ['client_id' => $client->getKey(), 'redirect_uri' => 'https://cloud.example.org/cb', 'response_type' => 'code', 'scope' => 'openid', 'code_challenge' => odB64(hash('sha256', $verifier, true)), 'code_challenge_method' => 'S256'];

    $this->actingAs(odUser(RoleKey::Team))->get('/oauth/authorize?'.http_build_query($params))->assertForbidden()->assertSee('nicht freigegeben');
    $this->actingAs(odUser(null))->get('/oauth/authorize?'.http_build_query($params))->assertForbidden();
    $this->actingAs(odUser(RoleKey::BoardMember))->get('/oauth/authorize?'.http_build_query($params))->assertRedirect();
});

it('refuses accounts that are not active', function () {
    $client = odClient();
    $params = ['client_id' => $client->getKey(), 'redirect_uri' => 'https://cloud.example.org/cb', 'response_type' => 'code', 'scope' => 'openid', 'code_challenge' => odB64(hash('sha256', 'v', true)), 'code_challenge_method' => 'S256'];

    $this->actingAs(odUser(RoleKey::Coordination, ['status' => UserStatus::Disabled]))->get('/oauth/authorize?'.http_build_query($params))->assertForbidden();
});

it('refuses an unregistered redirect address', function () {
    $client = odClient();
    $params = ['client_id' => $client->getKey(), 'redirect_uri' => 'https://evil.example.com/cb', 'response_type' => 'code', 'scope' => 'openid', 'code_challenge' => odB64(hash('sha256', 'v', true)), 'code_challenge_method' => 'S256'];

    $response = $this->actingAs(odUser())->get('/oauth/authorize?'.http_build_query($params));

    expect($response->getStatusCode())->toBeIn([400, 401])->and((string) $response->headers->get('Location'))->not->toContain('evil.example.com');
});

it('requires PKCE for public clients', function () {
    $client = odClient(['confidential' => false]);

    $response = $this->actingAs(odUser())->get('/oauth/authorize?'.http_build_query(['client_id' => $client->getKey(), 'redirect_uri' => 'https://cloud.example.org/cb', 'response_type' => 'code', 'scope' => 'openid']));

    $location = (string) $response->headers->get('Location');
    expect($response->getStatusCode() >= 400 || ($response->isRedirect() && str_contains($location, 'error=')))->toBeTrue()->and($location)->not->toContain('code=');
});

it('asks for consent when the connection is set up to show it', function () {
    $client = odClient(['show_consent' => true]);
    $params = ['client_id' => $client->getKey(), 'redirect_uri' => 'https://cloud.example.org/cb', 'response_type' => 'code', 'scope' => 'openid email', 'code_challenge' => odB64(hash('sha256', 'v', true)), 'code_challenge_method' => 'S256'];

    $response = $this->actingAs(odUser())->get('/oauth/authorize?'.http_build_query($params));

    expect($response->isRedirect())->toBeFalse();
});

it('rejects userinfo without a valid token and for a blocked connection or disabled account', function () {
    $this->getJson('/oauth/userinfo')->assertUnauthorized();

    $client = odClient();
    $user = odUser();
    $body = odFlow($this, $user, $client)['token']->json();

    app(OAuthClients::class)->setBlocked($client, true, odUser(RoleKey::Administration));
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', 'Bearer '.$body['access_token'])->getJson('/oauth/userinfo')->assertUnauthorized();

    app(OAuthClients::class)->setBlocked($client, false, odUser(RoleKey::Administration));
    DB::table('oauth_access_tokens')->update(['revoked' => false]);
    $user->forceFill(['status' => UserStatus::Disabled])->save();
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', 'Bearer '.$body['access_token'])->getJson('/oauth/userinfo')->assertUnauthorized();
});

it('logs out of the portal, revokes the tokens and redirects only to known addresses', function () {
    $client = odClient();
    $user = odUser();
    odFlow($this, $user, $client);
    expect(DB::table('oauth_access_tokens')->where('user_id', $user->id)->where('revoked', false)->count())->toBeGreaterThan(0);

    $this->actingAs($user)->get('/oauth/logout?post_logout_redirect_uri='.urlencode('https://cloud.example.org/bye'))->assertRedirect('https://cloud.example.org/bye');
    expect(DB::table('oauth_access_tokens')->where('user_id', $user->id)->where('revoked', false)->count())->toBe(0);

    $this->actingAs(odUser())->get('/oauth/logout?post_logout_redirect_uri='.urlencode('https://evil.example.com/x'))->assertRedirect(route('home'));
});

it('maps every role to a stable group name', function () {
    expect(OidcClaims::GROUPS)->toHaveKeys(['administration', 'board_member', 'administration_staff', 'coordination', 'education_coordination'])
        ->and(OidcClaims::GROUPS['coordination'])->toBe('vdbs-koordination')
        ->and(OidcClaims::GROUPS['board_member'])->toBe('vdbs-vorstand');
});

it('lists connected applications in the account and lets the person revoke one', function () {
    $client = odClient();
    $user = odUser();
    $body = odFlow($this, $user, $client)['token']->json();

    $this->actingAs($user)->get(route('account.apps'))->assertOk()->assertSee('Nextcloud');

    $this->actingAs($user)->delete(route('account.apps.destroy', $client->getKey()))->assertRedirect(route('account.apps'));
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', 'Bearer '.$body['access_token'])->getJson('/oauth/userinfo')->assertUnauthorized();
    $this->actingAs($user)->get(route('account.apps'))->assertOk()->assertDontSee('Nextcloud');
});

it('shows only the own applications and schedules the token cleanup', function () {
    $client = odClient();
    odFlow($this, odUser(), $client);

    $this->actingAs(odUser(null))->get(route('account.apps'))->assertOk()->assertDontSee('Nextcloud');
    expect(file_get_contents(base_path('routes/console.php')))->toContain("Schedule::command('passport:purge')->daily()");
});

<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Modules\Setup\Services\Setup;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    $this->lock = sys_get_temp_dir().'/vdbs-setup-test-'.bin2hex(random_bytes(4)).'.lock';
    config(['portal.setup.token' => str_repeat('t', 24), 'portal.setup.lock' => $this->lock]);
});

afterEach(function () {
    @unlink($this->lock);
});

function setupPost($test, array $data)
{
    return $test->post('/einrichtung', ['token' => str_repeat('t', 24)] + $data);
}

it('does not exist without a token or after the lock', function () {
    $this->get('/einrichtung')->assertOk()->assertSee('Token');

    config(['portal.setup.token' => null]);
    $this->get('/einrichtung')->assertNotFound();

    config(['portal.setup.token' => str_repeat('t', 24)]);
    file_put_contents($this->lock, 'x');
    $this->get('/einrichtung')->assertNotFound();
    $this->post('/einrichtung', ['token' => str_repeat('t', 24)])->assertNotFound();
});

it('refuses a wrong token and shows the requirements for the right one', function () {
    $this->post('/einrichtung', ['token' => 'falsch', 'step' => 'check'])->assertForbidden()->assertSee('Der Token stimmt nicht');

    setupPost($this, ['step' => 'check'])->assertOk()->assertSee('PHP-Version')->assertSee('Datenbank')->assertSee('Verbindung steht');
});

it('creates a key for the env file only when none is set', function () {
    setupPost($this, ['step' => 'key'])->assertOk()->assertSee('gesetzt');

    config(['app.key' => null]);
    setupPost($this, ['step' => 'key'])->assertOk()->assertSee('APP_KEY=base64:', false);
});

it('creates the database tables and the roles when asked', function () {
    setupPost($this, ['step' => 'database'])->assertOk()->assertSee('Datenbank jetzt anlegen');
    setupPost($this, ['step' => 'database', 'run' => '1'])->assertOk()->assertSee('Die Datenbank ist auf dem Stand');
});

it('creates the first administration with a strong password and the role', function () {
    $input = ['step' => 'admin', 'create' => '1', 'first_name' => 'Anna', 'last_name' => 'Admin', 'email' => 'anna@beispiel.example', 'birth_date' => '1985-04-12', 'password' => 'Sehr-Sicher-2026', 'password_confirmation' => 'Sehr-Sicher-2026'];

    setupPost($this, ['password' => 'kurz', 'password_confirmation' => 'kurz'] + $input)->assertOk()->assertSee('Bitte prüfen Sie die Eingaben')->assertSee('mindestens 12 Zeichen');
    setupPost($this, ['password_confirmation' => 'anders'] + $input)->assertOk()->assertSee('stimmen nicht überein');
    expect(User::query()->count())->toBe(0);

    setupPost($this, $input)->assertOk()->assertSee('Das Konto wurde angelegt');

    $user = User::query()->where('email', 'anna@beispiel.example')->firstOrFail();
    expect(Hash::check('Sehr-Sicher-2026', $user->password))->toBeTrue()
        ->and($user->activeRoleAssignments()->whereHas('role', fn ($role) => $role->where('key', RoleKey::Administration->value))->exists())->toBeTrue()
        ->and(Person::query()->where('email', 'anna@beispiel.example')->exists())->toBeTrue();

    setupPost($this, ['step' => 'admin', 'create' => '1', 'email' => 'zweite@beispiel.example'] + $input)->assertOk()->assertSee('Es gibt bereits eine Administration');
    expect(User::query()->count())->toBe(1);
});

it('refuses an address that is already taken', function () {
    $existing = User::factory()->create(['email' => 'anna@beispiel.example']);
    $input = ['step' => 'admin', 'create' => '1', 'first_name' => 'Anna', 'last_name' => 'Admin', 'email' => 'anna@beispiel.example', 'birth_date' => '1985-04-12', 'password' => 'Sehr-Sicher-2026', 'password_confirmation' => 'Sehr-Sicher-2026'];

    setupPost($this, $input)->assertOk()->assertSee('schon vergeben');
    expect(User::query()->count())->toBe(1)->and($existing->id)->not->toBeNull();
});

it('finishes only with an administration, writes the lock and then disappears', function () {
    $keys = Mockery::mock(Setup::class)->makePartial();
    $keys->shouldReceive('passportKeys')->andReturn(['ok' => true, 'message' => 'Schlüssel vorhanden']);
    app()->instance(Setup::class, $keys);

    setupPost($this, ['step' => 'finish'])->assertOk()->assertSee('noch keine Administration');
    expect(is_file($this->lock))->toBeFalse();

    setupPost($this, ['step' => 'admin', 'create' => '1', 'first_name' => 'Anna', 'last_name' => 'Admin', 'email' => 'anna@beispiel.example', 'birth_date' => '1985-04-12', 'password' => 'Sehr-Sicher-2026', 'password_confirmation' => 'Sehr-Sicher-2026']);
    setupPost($this, ['step' => 'finish'])->assertOk()->assertSee('abgeschlossen und gesperrt');

    expect(is_file($this->lock))->toBeTrue();
    $this->get('/einrichtung')->assertNotFound();
});

it('never writes the password into the audit log', function () {
    setupPost($this, ['step' => 'admin', 'create' => '1', 'first_name' => 'Anna', 'last_name' => 'Admin', 'email' => 'anna@beispiel.example', 'birth_date' => '1985-04-12', 'password' => 'Sehr-Sicher-2026', 'password_confirmation' => 'Sehr-Sicher-2026']);

    expect(json_encode(AuditEntry::query()->get()->toArray()))->not->toContain('Sehr-Sicher-2026');
});

<?php

use App\Console\Commands\ManageTestAccountsCommand;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function seedTestAccountRoles(): void
{
    (new RoleSeeder())->run();
}

function fixtureEmailFor(RoleKey $roleKey): string
{
    return sprintf(
        'test.%s@vdbs.test',
        str_replace('_', '-', $roleKey->value),
    );
}

it('creates one isolated test account for every role and writes private local credentials', function () {
    Storage::fake('local');
    seedTestAccountRoles();

    $this->artisan('vdbs:test-accounts', ['action' => 'create'])
        ->assertSuccessful();

    Storage::disk('local')->assertExists('test-accounts.json');

    $credentials = json_decode(
        Storage::disk('local')->get('test-accounts.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($credentials['accounts'])->toHaveCount(count(RoleKey::cases()));

    foreach (RoleKey::cases() as $roleKey) {
        $email = fixtureEmailFor($roleKey);

        $credential = collect($credentials['accounts'])
            ->firstWhere('role', $roleKey->value);

        expect($credential)
            ->toBeArray()
            ->and($credential['email'])
            ->toBe($email)
            ->and($credential['totp_secret'])
            ->toMatch('/^[A-Z2-7]{32}$/');

        $user = User::query()
            ->where('email', $email)
            ->firstOrFail();

        expect($user->status)
            ->toBe(UserStatus::Active)
            ->and($user->email_verified_at)
            ->not->toBeNull()
            ->and(Hash::check($credential['password'], $user->password))
            ->toBeTrue();

        $assignment = RoleAssignment::query()
            ->where('user_id', $user->id)
            ->sole();

        expect($assignment->source)
            ->toBe(RoleAssignmentSource::Console)
            ->and($assignment->source_type)
            ->toBe(ManageTestAccountsCommand::class)
            ->and($assignment->role?->key)
            ->toBe($roleKey->value);

        $method = TwoFactorMethod::query()
            ->where('user_id', $user->id)
            ->sole();

        expect($method->type)
            ->toBe(TwoFactorMethodType::Totp)
            ->and($method->secret)
            ->toBe($credential['totp_secret'])
            ->and($method->confirmed_at)
            ->not->toBeNull();
    }
});

it('recreates managed accounts with new credentials and invalidates prior sessions', function () {
    Storage::fake('local');
    seedTestAccountRoles();

    $this->artisan('vdbs:test-accounts', ['action' => 'create'])
        ->assertSuccessful();

    $email = fixtureEmailFor(RoleKey::Team);
    $user = User::query()->where('email', $email)->firstOrFail();
    $oldSessionVersion = $user->session_version;

    $oldCredentials = json_decode(
        Storage::disk('local')->get('test-accounts.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    $oldPassword = collect($oldCredentials['accounts'])
        ->firstWhere('role', RoleKey::Team->value)['password'];

    $this->artisan('vdbs:test-accounts', ['action' => 'create'])
        ->assertSuccessful();

    $user->refresh();

    $newCredentials = json_decode(
        Storage::disk('local')->get('test-accounts.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    $newPassword = collect($newCredentials['accounts'])
        ->firstWhere('role', RoleKey::Team->value)['password'];

    expect($user->session_version)
        ->toBe($oldSessionVersion + 1)
        ->and($newPassword)
        ->not->toBe($oldPassword)
        ->and(Hash::check($newPassword, $user->password))
        ->toBeTrue();
});

it('deletes only managed test accounts and leaves the configured development admin untouched', function () {
    Storage::fake('local');
    seedTestAccountRoles();

    config()->set('development.admin.email', 'default-admin@vdbs.test');

    $adminPassword = Str::random(32);
    $adminTotpSecret = str_repeat('A', 32);

    $admin = User::query()->create([
        'email' => 'default-admin@vdbs.test',
        'password' => $adminPassword,
        'status' => UserStatus::Active,
        'session_version' => 7,
    ]);
    $admin->email_verified_at = now();
    $admin->save();

    TwoFactorMethod::query()->create([
        'user_id' => $admin->id,
        'type' => TwoFactorMethodType::Totp,
        'secret' => $adminTotpSecret,
        'confirmed_at' => now(),
        'disabled_at' => null,
    ]);

    $this->artisan('vdbs:test-accounts', ['action' => 'create'])
        ->assertSuccessful();

    $this->artisan('vdbs:test-accounts', ['action' => 'delete'])
        ->assertSuccessful();

    foreach (RoleKey::cases() as $roleKey) {
        expect(
            User::query()
                ->where('email', fixtureEmailFor($roleKey))
                ->exists(),
        )->toBeFalse();
    }

    $admin->refresh();

    expect($admin->email)
        ->toBe('default-admin@vdbs.test')
        ->and($admin->session_version)
        ->toBe(7)
        ->and(Hash::check($adminPassword, $admin->password))
        ->toBeTrue()
        ->and(
            TwoFactorMethod::query()
                ->where('user_id', $admin->id)
                ->value('secret'),
        )
        ->toBe($adminTotpSecret);

    Storage::disk('local')->assertMissing('test-accounts.json');
});

it('refuses to take over an existing regular account with a fixture email', function () {
    Storage::fake('local');
    seedTestAccountRoles();

    $email = fixtureEmailFor(RoleKey::Guest);

    $person = Person::query()->create([
        'first_name' => 'Regulär',
        'last_name' => 'Bestehend',
        'birth_date' => '1990-01-01',
        'email' => $email,
        'country_code' => 'DE',
    ]);

    User::query()->create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => Str::random(32),
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $this->artisan('vdbs:test-accounts', ['action' => 'create'])
        ->assertFailed();

    expect(
        RoleAssignment::query()
            ->where('source_type', ManageTestAccountsCommand::class)
            ->exists(),
    )->toBeFalse();

    Storage::disk('local')->assertMissing('test-accounts.json');
});

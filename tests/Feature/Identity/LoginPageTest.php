<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Livewire\Volt\Volt;

it('shows the login page only on the my domain', function () {
    $this
        ->get('http://my.vdb.test/anmelden')
        ->assertOk()
        ->assertSee('Anmelden');

    $this
        ->get(
            'http://verwaltung.vdb.test/anmelden'
        )
        ->assertNotFound();
});

it('logs in through the login page', function () {
    $user = User::query()->create([
        'email' => 'erika@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    Volt::test('identity.login')
        ->set('email', 'ERIKA@example.test')
        ->set('password', 'Sicher123!')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(
            route('my.home')
        );

    $this->assertAuthenticatedAs($user);
});

it('shows a neutral login error', function () {
    Volt::test('identity.login')
        ->set(
            'email',
            'unknown@example.test'
        )
        ->set(
            'password',
            'Sicher123!'
        )
        ->call('login')
        ->assertSet(
            'loginError',
            'Die eingegebenen Zugangsdaten sind ungültig.'
        );

    $this->assertGuest();
});

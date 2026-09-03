<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

function makeSessionUser(): User
{
    $user = User::query()->create([
        'email' => 'session@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('allows a valid authenticated session', function () {
    $user = makeSessionUser();

    $this
        ->actingAs($user)
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' =>
                now()->subMinutes(11)->timestamp,
        ])
        ->get('http://my.vdb.test/')
        ->assertOk();
});

it('invalidates a stale session version', function () {
    $user = makeSessionUser();

    $this
        ->actingAs($user)
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' =>
                now()->subMinutes(11)->timestamp,
        ]);

    $user->update([
        'session_version' => 2,
    ]);

    $this
        ->get('http://my.vdb.test/')
        ->assertRedirect(
            route('my.login')
        );

    $this->assertGuest();
});

it('invalidates a disabled user during revalidation', function () {
    $user = makeSessionUser();

    $this
        ->actingAs($user)
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' =>
                now()->subMinutes(11)->timestamp,
        ]);

    $user->update([
        'status' => UserStatus::Disabled,
    ]);

    $this
        ->get('http://my.vdb.test/')
        ->assertRedirect(
            route('my.login')
        );

    $this->assertGuest();
});
<?php

use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;

function makeTwoFactorSessionUser(): User
{
    $user = User::query()->create([
        'email' => '2fa-session@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 2,
    ]);

    $user->email_verified_at = now();
    $user->save();

    TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Email,
        'confirmed_at' => now(),
    ]);

    return $user->refresh();
}

it('invalidates a two factor session without second factor proof', function () {
    $user = makeTwoFactorSessionUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()
                ->subMinutes(11)
                ->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/')
        ->assertRedirect(
            route('my.login')
        );

    $this->assertGuest();
});

it('keeps a valid session that proved the second factor', function () {
    $user = makeTwoFactorSessionUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()
                ->subMinutes(11)
                ->timestamp,
            'identity.two_factor_verified_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/')
        ->assertOk();

    $this->assertAuthenticatedAs(
        $user
    );
});

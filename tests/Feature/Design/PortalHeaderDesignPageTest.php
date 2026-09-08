<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

function makePortalHeaderDesignUser(): User
{
    return User::query()->create([
        'email' => 'header-design@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('shows the portal header reference page', function () {
    $user = makePortalHeaderDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/header')
        ->assertOk()
        ->assertSeeText('Portal-Header')
        ->assertSeeText('Verwaltung')
        ->assertSeeText('Design')
        ->assertSeeText('Über das Portal')
        ->assertSeeText('Zugang zum Portal')
        ->assertSeeText('Mein Profil')
        ->assertSeeText('Kontoeinstellungen')
        ->assertSeeText('Meine Tickets')
        ->assertSeeText('Abmelden');
});

it('ships the local vdbs logo asset', function () {
    expect(
        file_exists(
            public_path('images/brand/vdbs-logo.png')
        )
    )->toBeTrue();
});

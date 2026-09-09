<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

function makePortalLayoutDesignUser(): User
{
    return User::query()->create([
        'email' => 'portal-layout@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('uses the portal shell on existing public identity pages', function () {
    $this
        ->get('http://my.vdb.test/anmelden')
        ->assertOk()
        ->assertSee('site-header', false)
        ->assertSee('header-top', false)
        ->assertSee('header-bottom', false)
        ->assertSeeText('VDBS Portal')
        ->assertSeeText('Anmelden')
        ->assertSee('vdbs-footer', false);
});

it('uses the horizontal portal structure in the design area without a sidebar', function () {
    $user = makePortalLayoutDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design')
        ->assertOk()
        ->assertSee('site-header', false)
        ->assertSeeText('Designsystem')
        ->assertSeeText('Grundlagen')
        ->assertSeeText('Elemente')
        ->assertSeeText('Vorlagen')
        ->assertDontSee('design-sidebar', false);
});

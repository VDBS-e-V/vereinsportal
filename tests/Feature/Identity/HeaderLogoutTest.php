<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

it('logs out through the authenticated header logout endpoint', function () {
    $user = User::query()->create([
        'email' => 'header-logout@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);

    $response = $this
        ->withSession([
            'identity.session_version' => 1,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->post('http://my.vdb.test/abmelden');

    $response
        ->assertRedirect(route('my.login'))
        ->assertSessionHas(
            'status',
            'Sie wurden abgemeldet.',
        )
        ->assertSessionHas(
            'status_type',
            'success',
        );

    $this->assertGuest();

    expect(
        AuditEvent::query()
            ->where(
                'event_key',
                AuditEventCatalog::AUTH_LOGOUT,
            )
            ->where(
                'actor_user_id',
                $user->id,
            )
            ->count()
    )->toBe(1);
});
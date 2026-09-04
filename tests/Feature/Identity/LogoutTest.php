<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Auth\LogoutAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

it('logs out only the current session and audits it', function () {
    $user = User::query()->create([
        'email' => 'logout@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);

    $this->actingAs($user);

    app(LogoutAction::class)->execute(
        user: $user,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Browser',
    );

    $this->assertGuest();

    expect(
        User::query()
            ->findOrFail($user->id)
            ->session_version
    )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_LOGOUT,
                )
                ->count()
        )
        ->toBe(1);
});

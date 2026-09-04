<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\TwoFactor\RegenerateRecoveryCodesAction;
use App\Modules\Identity\Actions\TwoFactor\UseRecoveryCodeAction;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;

function makeRecoveryCodeUser(): User
{
    $user = User::query()->create([
        'email' => 'recovery@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
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

it('uses a recovery code only once without storing its plaintext in audit', function () {
    $user = makeRecoveryCodeUser();

    $codes = app(
        RegenerateRecoveryCodesAction::class
    )->execute($user);

    $code = $codes[0];

    app(
        UseRecoveryCodeAction::class
    )->execute(
        user: $user,
        code: $code,
        ipAddress: '127.0.0.1',
    );

    expect(
        TwoFactorRecoveryCode::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->whereNotNull('used_at')
            ->count()
    )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_2FA_RECOVERY_CODE_USED,
                )
                ->count()
        )
        ->toBe(1);

    expect(
        fn () => app(
            UseRecoveryCodeAction::class
        )->execute(
            user: $user,
            code: $code,
            ipAddress: '127.0.0.1',
        )
    )->toThrow(
        TwoFactorChallengeFailed::class
    );

    $auditJson = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::AUTH_2FA_RECOVERY_CODE_USED,
        )
        ->get()
        ->toJson();

    expect($auditJson)
        ->not->toContain($code);
});

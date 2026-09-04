<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\TwoFactor\BeginTotpSetupAction;
use App\Modules\Identity\Actions\TwoFactor\ConfirmTotpSetupAction;
use App\Modules\Identity\Actions\TwoFactor\RegenerateRecoveryCodesAction;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\TotpService;
use Illuminate\Support\Facades\Hash;

function makeTwoFactorSetupUser(): User
{
    $user = User::query()->create([
        'email' => 'twofactor@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('does not activate an unconfirmed totp secret', function () {
    $user = makeTwoFactorSetupUser();

    $setup = app(
        BeginTotpSetupAction::class
    )->execute($user);

    $method = $setup['method']
        ->refresh();

    expect($method->type)
        ->toBe(TwoFactorMethodType::Totp)
        ->and($method->confirmed_at)
        ->toBeNull()
        ->and($method->disabled_at)
        ->toBeNull();
});

it('confirms totp and creates exactly four hashed recovery codes', function () {
    $this->travelTo(
        now()->startOfSecond()
    );

    $user = makeTwoFactorSetupUser();

    $setup = app(
        BeginTotpSetupAction::class
    )->execute($user);

    $totp = app(TotpService::class);

    /*
     * Für den Test lesen wir den intern verschlüsselten
     * Wert regulär über den encrypted Cast aus.
     */
    $method =
        TwoFactorMethod::query()
            ->findOrFail(
                $setup['method']->id
            );

    $reflection =
        new ReflectionClass(
            TotpService::class
        );

    $codeMethod =
        $reflection->getMethod(
            'codeForCounter'
        );

    $codeMethod->setAccessible(true);

    $code = $codeMethod->invoke(
        $totp,
        $method->secret,
        intdiv(
            now()->timestamp,
            30,
        ),
    );

    $plainCodes = app(
        ConfirmTotpSetupAction::class
    )->execute(
        user: $user,
        methodId: $method->id,
        code: $code,
        ipAddress: '127.0.0.1',
    );

    $method->refresh();

    expect($method->confirmed_at)
        ->not->toBeNull()
        ->and($plainCodes)
        ->toHaveCount(4)
        ->and(
            TwoFactorRecoveryCode::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count()
        )
        ->toBe(4)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_2FA_ENABLED,
                )
                ->count()
        )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_2FA_RECOVERY_CODES_REGENERATED,
                )
                ->count()
        )
        ->toBe(1);

    $stored =
        TwoFactorRecoveryCode::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->get();

    foreach ($plainCodes as $plainCode) {
        expect(
            $stored->contains(
                fn ($record): bool => Hash::check(
                    str_replace(
                        '-',
                        '',
                        $plainCode,
                    ),
                    $record->code_hash,
                )
            )
        )->toBeTrue();
    }
});

it('rejects a wrong initial totp code', function () {
    $user = makeTwoFactorSetupUser();

    $setup = app(
        BeginTotpSetupAction::class
    )->execute($user);

    expect(
        fn () => app(
            ConfirmTotpSetupAction::class
        )->execute(
            user: $user,
            methodId: $setup['method']->id,
            code: '999999',
        )
    )->toThrow(
        TwoFactorSetupFailed::class
    );

    $setup['method']->refresh();

    expect(
        $setup['method']->confirmed_at
    )->toBeNull();
});

it('regenerates exactly four recovery codes and invalidates old ones', function () {
    $user = makeTwoFactorSetupUser();

    /*
     * Für diesen Test reicht eine aktive
     * E-Mail-2FA-Methode.
     */
    TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Email,
        'confirmed_at' => now(),
    ]);

    $action = app(
        RegenerateRecoveryCodesAction::class
    );

    $first = $action->execute($user);

    $oldIds =
        TwoFactorRecoveryCode::query()
            ->pluck('id');

    $second = $action->execute($user);

    expect($first)
        ->toHaveCount(4)
        ->and($second)
        ->toHaveCount(4)
        ->and(
            TwoFactorRecoveryCode::query()
                ->whereIn('id', $oldIds)
                ->whereNotNull(
                    'invalidated_at'
                )
                ->count()
        )
        ->toBe(4)
        ->and(
            TwoFactorRecoveryCode::query()
                ->whereNull('used_at')
                ->whereNull(
                    'invalidated_at'
                )
                ->count()
        )
        ->toBe(4);
});

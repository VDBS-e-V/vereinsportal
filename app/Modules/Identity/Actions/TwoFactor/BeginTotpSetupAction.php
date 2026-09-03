<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\TotpService;
use Illuminate\Support\Facades\DB;

final class BeginTotpSetupAction
{
    public function __construct(
        private readonly TotpService $totp,
    ) {
    }

    /**
     * @return array{
     *     method: TwoFactorMethod,
     *     secret: string,
     *     provisioning_uri: string
     * }
     */
    public function execute(
        User $user,
    ): array {
        return DB::transaction(
            function () use ($user): array {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedUser->status
                        !== UserStatus::Active
                    || $lockedUser->email_verified_at
                        === null
                ) {
                    throw TwoFactorSetupFailed::
                        invalidMethod();
                }

                /*
                 * Alte, niemals bestätigte Setups
                 * werden verworfen. Eine noch aktive
                 * bestätigte TOTP-Methode bleibt bis zur
                 * erfolgreichen Bestätigung der neuen bestehen.
                 */
                TwoFactorMethod::query()
                    ->where(
                        'user_id',
                        $lockedUser->id,
                    )
                    ->where(
                        'type',
                        TwoFactorMethodType::Totp,
                    )
                    ->whereNull('confirmed_at')
                    ->whereNull('disabled_at')
                    ->update([
                        'disabled_at' => now(),
                    ]);

                $secret =
                    $this->totp->generateSecret();

                $method =
                    TwoFactorMethod::query()
                        ->create([
                            'user_id' =>
                                $lockedUser->id,
                            'type' =>
                                TwoFactorMethodType::Totp,
                            'secret' => $secret,
                            'confirmed_at' => null,
                        ]);

                return [
                    'method' => $method,
                    'secret' => $secret,
                    'provisioning_uri' =>
                        $this->totp
                            ->provisioningUri(
                                account:
                                    $lockedUser->email,
                                secret: $secret,
                            ),
                ];
            },
        );
    }
}
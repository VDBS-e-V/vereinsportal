<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Exceptions\TwoFactorSetupFailed;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Illuminate\Support\Facades\DB;

final class SetPreferredTwoFactorMethodAction
{
    public function __construct(
        private readonly TwoFactorRequirement $requirement,
    ) {}

    public function execute(
        User $user,
        TwoFactorMethodType $type,
    ): User {
        return DB::transaction(function () use ($user, $type): User {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->requirement->canUse($lockedUser, $type)) {
                throw TwoFactorSetupFailed::invalidMethod();
            }

            $lockedUser->preferred_two_factor_method = $type;
            $lockedUser->save();

            return $lockedUser->refresh();
        });
    }
}

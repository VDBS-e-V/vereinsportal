<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Models\TwoFactorRecoveryCode;
use App\Modules\Identity\Models\User;

final class TwoFactorRequirement
{
    public function isRequiredByRole(
        User $user,
    ): bool {
        return $user->roleAssignments()
            ->whereHas(
                'role',
                function ($query): void {
                    $query->whereIn(
                        'key',
                        [
                            RoleKey::BoardMember->value,
                            RoleKey::Administration->value,
                        ],
                    );
                },
            )
            ->where(
                'starts_at',
                '<=',
                now(),
            )
            ->where(function ($query): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere(
                        'ends_at',
                        '>',
                        now(),
                    );
            })
            ->exists();
    }

    public function hasActiveMethod(
        User $user,
    ): bool {
        return $user->twoFactorMethods()
            ->whereNotNull('confirmed_at')
            ->whereNull('disabled_at')
            ->exists();
    }

    public function requiresChallenge(
        User $user,
    ): bool {
        return $this->isRequiredByRole($user)
            || $this->hasActiveMethod($user);
    }

    public function canUseEmail(
        User $user,
    ): bool {
        /*
         * Für Pflichtrollen ist die verifizierte
         * Account-E-Mail immer als zweiter Faktor
         * verfügbar.
         */
        if ($this->isRequiredByRole($user)) {
            return true;
        }

        return $user->twoFactorMethods()
            ->where(
                'type',
                TwoFactorMethodType::Email,
            )
            ->whereNotNull('confirmed_at')
            ->whereNull('disabled_at')
            ->exists();
    }

    public function canUseTotp(
        User $user,
    ): bool {
        return $user->twoFactorMethods()
            ->where(
                'type',
                TwoFactorMethodType::Totp,
            )
            ->whereNotNull('confirmed_at')
            ->whereNull('disabled_at')
            ->exists();
    }

    public function canUse(
        User $user,
        TwoFactorMethodType $type,
    ): bool {
        return match ($type) {
            TwoFactorMethodType::Email => $this->canUseEmail($user),
            TwoFactorMethodType::Totp => $this->canUseTotp($user),
        };
    }

    /** @return list<TwoFactorMethodType> */
    public function availableMethods(User $user): array
    {
        $methods = [];

        if ($this->canUseTotp($user)) {
            $methods[] = TwoFactorMethodType::Totp;
        }

        if ($this->canUseEmail($user)) {
            $methods[] = TwoFactorMethodType::Email;
        }

        return $methods;
    }

    public function defaultChallengeMethod(
        User $user,
    ): ?TwoFactorMethodType {
        $preferred = $user->preferred_two_factor_method;

        if (
            $preferred instanceof TwoFactorMethodType
            && $this->canUse($user, $preferred)
        ) {
            return $preferred;
        }

        return $this->availableMethods($user)[0] ?? null;
    }

    public function hasRecoveryCodes(User $user): bool
    {
        return TwoFactorRecoveryCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->whereNull('invalidated_at')
            ->exists();
    }
}

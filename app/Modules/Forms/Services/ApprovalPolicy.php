<?php

namespace App\Modules\Forms\Services;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Forms\Models\FormApplication;

/**
 * Wer darf einen Antrag prüfen und freigeben: Bis zur Grenze gibt die Geschäftsstelle frei, darüber zusätzlich eine Person mit
 * Freigaberecht (Geschäftsführung oder Vorstand). Eigene Anträge gibt niemand frei; prüfen darf nur die Kassenführung (Selbstprüfung, sichtbar vermerkt).
 */
final class ApprovalPolicy
{
    public function __construct(private readonly FormSettings $settings) {}

    public function level(int $totalCents): string
    {
        return $totalCents > $this->settings->approvalLimit() ? 'board' : 'office';
    }

    private function isApplicant(User $user, FormApplication $application): bool
    {
        return $application->applicant_user_id !== null && $application->applicant_user_id === $user->id;
    }

    public function canCheck(User $user, FormApplication $application): bool
    {
        if (! Capabilities::has($user, Capability::FormsManage)) {
            return false;
        }

        return ! $this->isApplicant($user, $application) || in_array(RoleKey::Treasury->value, Capabilities::activeRoleKeys($user), true);
    }

    public function isSelfCheck(User $user, FormApplication $application): bool
    {
        return $this->isApplicant($user, $application);
    }

    public function canApprove(User $user, FormApplication $application): bool
    {
        if ($this->isApplicant($user, $application)) {
            return false;
        }

        if ($application->approval_level === 'board') {
            return Capabilities::has($user, Capability::FormsApprove) && $application->checked_by_user_id !== $user->id;
        }

        return Capabilities::has($user, Capability::FormsManage) || Capabilities::has($user, Capability::FormsApprove);
    }
}

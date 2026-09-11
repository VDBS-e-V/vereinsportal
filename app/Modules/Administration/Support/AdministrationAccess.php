<?php

namespace App\Modules\Administration\Support;

use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;

final class AdministrationAccess
{
    /**
     * Beta role presets. Keep these definitions centralized so controllers
     * and routes depend on capabilities rather than role names.
     *
     * @var array<string, list<string>>
     */
    private const ROLE_CAPABILITIES = [
        'administration_staff' => [
            'persons.read',
            'memberships.read',
            'membership_documents.read',
            'membership_consents.read',
            'users.read',
            'communication.read',
        ],
        'administration' => [
            'persons.read',
            'persons.manage',
            'memberships.read',
            'memberships.manage',
            'membership_documents.read',
            'membership_documents.manage',
            'membership_consents.read',
            'membership_consents.manage',
            'portal_invitations.manage',
            'users.read',
            'users.status.manage',
            'roles.manage',
            'communication.read',
            'communication.manage',
            'audit.read',
        ],
    ];

    public function allows(?User $user): bool
    {
        return $this->hasAnyActiveRole(
            $user,
            array_keys(self::ROLE_CAPABILITIES),
        );
    }

    public function allowsCapability(
        ?User $user,
        AdministrationCapability $capability,
    ): bool {
        $roleKeys = [];

        foreach (self::ROLE_CAPABILITIES as $roleKey => $capabilities) {
            if (in_array($capability->value, $capabilities, true)) {
                $roleKeys[] = $roleKey;
            }
        }

        return $this->hasAnyActiveRole($user, $roleKeys);
    }

    /**
     * @return list<AdministrationCapability>
     */
    public function capabilities(?User $user): array
    {
        if (! $this->isEligibleUser($user)) {
            return [];
        }

        $now = now();
        $activeRoleKeys = $user
            ->roleAssignments()
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })
            ->whereHas(
                'role',
                fn ($query) => $query->whereIn(
                    'key',
                    array_keys(self::ROLE_CAPABILITIES),
                ),
            )
            ->with('role:id,key')
            ->get()
            ->map(fn ($assignment) => $assignment->role?->key)
            ->filter(fn ($key) => is_string($key))
            ->unique()
            ->values();

        $capabilityValues = [];

        foreach ($activeRoleKeys as $roleKey) {
            foreach (self::ROLE_CAPABILITIES[$roleKey] ?? [] as $capability) {
                $capabilityValues[$capability] = true;
            }
        }

        return array_map(
            static fn (string $value): AdministrationCapability => AdministrationCapability::from($value),
            array_keys($capabilityValues),
        );
    }

    /**
     * Compatibility helper for legacy callers. New authorization decisions
     * must use allowsCapability() with the concrete fachliche capability.
     */
    public function canManage(?User $user): bool
    {
        return $this->allowsCapability(
            $user,
            AdministrationCapability::PersonsManage,
        );
    }

    /**
     * @param  list<string>  $roleKeys
     */
    private function hasAnyActiveRole(
        ?User $user,
        array $roleKeys,
    ): bool {
        if (! $this->isEligibleUser($user) || $roleKeys === []) {
            return false;
        }

        $now = now();

        return $user
            ->roleAssignments()
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })
            ->whereHas(
                'role',
                fn ($query) => $query->whereIn('key', $roleKeys),
            )
            ->exists();
    }

    private function isEligibleUser(?User $user): bool
    {
        return $user !== null
            && $user->status === UserStatus::Active
            && $user->email_verified_at !== null;
    }
}

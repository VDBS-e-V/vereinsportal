<?php

namespace App\Modules\Membership\Services;

use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;

/**
 * Steuert die Rolle „Vereinsmitglied“ aus den Mitgliedschaften einer Person: Je aufgenommener Mitgliedschaft gibt es eine
 * automatische Rollenzuordnung (Quelle: die Mitgliedschaft) vom Beitrittsdatum bis zum Ende des Austrittsdatums.
 * Von Hand vergebene Rollen bleiben unberührt. Hat die Person noch kein Konto, passiert nichts; beim Anlegen des
 * Kontos wird nachgezogen. Die Methoden sind idempotent und protokollieren bewusst nichts im allgemeinen Protokoll.
 */
final class MembershipRoles
{
    public const SOURCE_TYPE = 'membership';

    public function syncPerson(Person $person): void
    {
        $user = $person->user()->first();
        $role = Role::query()->where('key', RoleKey::Member->value)->first();

        if ($user === null || ! $role instanceof Role) {
            return;
        }

        foreach ($person->memberships()->get() as $membership) {
            $this->syncMembership($membership, $user->id, $role->id);
        }
    }

    private function syncMembership(Membership $membership, int $userId, int $roleId): void
    {
        $assignment = RoleAssignment::query()
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where('source_type', self::SOURCE_TYPE)
            ->where('source_id', $membership->id)
            ->first();

        $grants = in_array($membership->status, [MembershipStatus::Active, MembershipStatus::Ended], true) && $membership->starts_on !== null;

        if (! $grants) {
            if ($assignment instanceof RoleAssignment && ($assignment->ends_at === null || $assignment->ends_at->isFuture())) {
                $assignment->forceFill(['ends_at' => now()])->save();
            }

            return;
        }

        $values = [
            'starts_at' => $membership->starts_on->copy()->startOfDay(),
            'ends_at' => $membership->ends_on?->copy()->endOfDay(),
        ];

        if ($assignment instanceof RoleAssignment) {
            $assignment->forceFill($values)->save();

            return;
        }

        RoleAssignment::query()->create($values + [
            'user_id' => $userId,
            'role_id' => $roleId,
            'source' => RoleAssignmentSource::Automatic,
            'source_type' => self::SOURCE_TYPE,
            'source_id' => $membership->id,
            'comment' => 'Mitgliedschaft',
        ]);
    }
}

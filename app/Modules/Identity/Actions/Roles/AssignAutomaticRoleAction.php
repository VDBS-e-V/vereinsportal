<?php

namespace App\Modules\Identity\Actions\Roles;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AssignAutomaticRoleAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
    }

    public function execute(
        User $user,
        RoleKey $roleKey,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): RoleAssignment {
        $this->ensureRoleCanBeAssignedAutomatically($roleKey);

        return DB::transaction(function () use (
            $user,
            $roleKey,
            $sourceType,
            $sourceId,
        ): RoleAssignment {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $role = Role::query()
                ->where('key', $roleKey->value)
                ->firstOrFail();

            $existingAssignment = RoleAssignment::query()
                ->where('user_id', $lockedUser->id)
                ->where('role_id', $role->id)
                ->whereNull('ends_at')
                ->lockForUpdate()
                ->first();

            if ($existingAssignment !== null) {
                return $existingAssignment;
            }

            $assignment = RoleAssignment::query()->create([
                'user_id' => $lockedUser->id,
                'role_id' => $role->id,
                'source' => RoleAssignmentSource::Automatic,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'starts_at' => now(),
            ]);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ROLE_AUTOMATIC_ASSIGNED,
                actorType: AuditActorType::System,
                subjectType: 'role_assignment',
                subjectId: $assignment->id,
                newValues: [
                    'role' => $roleKey->value,
                    'source' => RoleAssignmentSource::Automatic->value,
                ],
            );

            return $assignment;
        });
    }

    private function ensureRoleCanBeAssignedAutomatically(
        RoleKey $roleKey,
    ): void {
        if (! in_array($roleKey, [
            RoleKey::Guest,
            RoleKey::Member,
            RoleKey::BoardMember,
        ], true)) {
            throw new InvalidArgumentException(
                "Role [{$roleKey->value}] cannot be assigned automatically."
            );
        }
    }
}
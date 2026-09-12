<?php

namespace App\Modules\Administration\Actions;

use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Exceptions\AdministrationActionRejected;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class AssignManualRoleAction
{
    public function __construct(
        private readonly AdministrationAccess $access,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $target,
        User $actor,
        RoleKey $roleKey,
        string $comment,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): RoleAssignment {
        if (! $this->access->allowsCapability(
            $actor,
            AdministrationCapability::RolesManage,
        )) {
            throw new AdministrationActionRejected(
                'Für diese Aktion fehlt die erforderliche Administrationsberechtigung.'
            );
        }

        $normalizedComment = trim($comment);

        if ($normalizedComment === '') {
            throw new AdministrationActionRejected(
                'Für die Rollenzuweisung ist eine Begründung erforderlich.'
            );
        }

        return DB::transaction(function () use (
            $target,
            $actor,
            $roleKey,
            $normalizedComment,
            $ipAddress,
            $userAgent,
        ): RoleAssignment {
            $lockedTarget = User::query()
                ->whereKey($target->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedTarget->status, [
                UserStatus::PendingDeletion,
                UserStatus::Anonymized,
            ], true)) {
                throw new AdministrationActionRejected(
                    'Für dieses Konto kann keine neue Rolle zugewiesen werden.'
                );
            }

            $role = Role::query()
                ->where('key', $roleKey->value)
                ->lockForUpdate()
                ->first();

            if ($role === null) {
                throw new AdministrationActionRejected(
                    'Die ausgewählte Rolle ist nicht verfügbar.'
                );
            }

            $now = now();

            $alreadyAssigned = RoleAssignment::query()
                ->where('user_id', $lockedTarget->id)
                ->where('role_id', $role->id)
                ->where(function ($query) use ($now): void {
                    $query
                        ->whereNull('ends_at')
                        ->orWhere(
                            'ends_at',
                            '>',
                            $now,
                        );
                })
                ->lockForUpdate()
                ->exists();

            if ($alreadyAssigned) {
                throw new AdministrationActionRejected(
                    'Diese Rolle ist bereits aktiv oder vorgemerkt zugewiesen.'
                );
            }

            $assignment = RoleAssignment::query()->create([
                'user_id' => $lockedTarget->id,
                'role_id' => $role->id,
                'source' => RoleAssignmentSource::Manual,
                'starts_at' => $now,
                'granted_by_user_id' => $actor->id,
                'comment' => $normalizedComment,
            ]);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ROLE_MANUAL_ASSIGNED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'role_assignment',
                subjectId: $assignment->id,
                newValues: [
                    'role' => $roleKey->value,
                    'source' => RoleAssignmentSource::Manual->value,
                    'starts_at' => $now->toIso8601String(),
                ],
                comment: $normalizedComment,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $now,
            );

            return $assignment;
        });
    }
}

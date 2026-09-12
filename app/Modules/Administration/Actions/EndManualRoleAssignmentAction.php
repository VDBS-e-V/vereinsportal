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
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class EndManualRoleAssignmentAction
{
    public function __construct(
        private readonly AdministrationAccess $access,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $target,
        RoleAssignment $assignment,
        User $actor,
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
                'Für das Beenden der Rollenzuweisung ist eine Begründung erforderlich.'
            );
        }

        return DB::transaction(function () use (
            $target,
            $assignment,
            $actor,
            $normalizedComment,
            $ipAddress,
            $userAgent,
        ): RoleAssignment {
            $lockedAssignment = RoleAssignment::query()
                ->with('role')
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedAssignment->user_id !== $target->id
                || $lockedAssignment->source !== RoleAssignmentSource::Manual
            ) {
                throw new AdministrationActionRejected(
                    'Diese Rollenzuweisung kann in der Verwaltung nicht beendet werden.'
                );
            }

            $now = now();

            if (
                $lockedAssignment->starts_at->gt($now)
                || (
                    $lockedAssignment->ends_at !== null
                    && $lockedAssignment->ends_at->lte($now)
                )
            ) {
                throw new AdministrationActionRejected(
                    'Diese Rollenzuweisung ist nicht aktiv.'
                );
            }

            if (
                $target->id === $actor->id
                && $lockedAssignment->role?->key
                    === RoleKey::Administration->value
            ) {
                throw new AdministrationActionRejected(
                    'Die eigene aktive Administrationsrolle kann hier nicht beendet werden.'
                );
            }

            $lockedAssignment->ends_at = $now;
            $lockedAssignment->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ROLE_MANUAL_ENDED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'role_assignment',
                subjectId: $lockedAssignment->id,
                oldValues: [
                    'role' => $lockedAssignment->role?->key,
                    'source' => RoleAssignmentSource::Manual->value,
                ],
                newValues: [
                    'role' => $lockedAssignment->role?->key,
                    'source' => RoleAssignmentSource::Manual->value,
                    'ends_at' => $now->toIso8601String(),
                ],
                comment: $normalizedComment,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $now,
            );

            return $lockedAssignment;
        });
    }
}

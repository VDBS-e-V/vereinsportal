<?php

namespace App\Modules\Membership\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;
use Illuminate\Support\Facades\DB;

final class SynchronizeMembershipRoleAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(Membership $membership): ?RoleAssignment
    {
        $membership->loadMissing('person.user');
        $user = $membership->person->user;

        if (! $user instanceof User) {
            return null;
        }

        return DB::transaction(function () use (
            $membership,
            $user,
        ): RoleAssignment {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $role = Role::query()
                ->where('key', RoleKey::Member->value)
                ->firstOrFail();

            $startsAt = $membership->starts_on->copy()->startOfDay();
            $endsAt = $membership->ends_on?->copy()->endOfDay();

            $assignment = RoleAssignment::query()
                ->where('user_id', $lockedUser->id)
                ->where('role_id', $role->id)
                ->where('source', RoleAssignmentSource::Automatic->value)
                ->where('source_type', 'membership')
                ->where('source_id', $membership->id)
                ->lockForUpdate()
                ->first();

            if ($assignment === null) {
                $assignment = RoleAssignment::query()->create([
                    'user_id' => $lockedUser->id,
                    'role_id' => $role->id,
                    'source' => RoleAssignmentSource::Automatic,
                    'source_type' => 'membership',
                    'source_id' => $membership->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::ROLE_AUTOMATIC_ASSIGNED,
                    actorType: AuditActorType::System,
                    subjectType: 'role_assignment',
                    subjectId: $assignment->id,
                    newValues: [
                        'role' => RoleKey::Member->value,
                        'source' => RoleAssignmentSource::Automatic->value,
                    ],
                );

                return $assignment;
            }

            $assignment->starts_at = $startsAt;
            $assignment->ends_at = $endsAt;

            if ($assignment->isDirty()) {
                $assignment->save();
            }

            return $assignment->refresh();
        });
    }
}

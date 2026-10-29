<?php

namespace App\Modules\AccessGovernance\Services;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use DomainException;
use Illuminate\Support\Carbon;

/**
 * Manuelle Rollenvergabe. Eine Zuordnung endet durch ein Enddatum, sie wird nie gelöscht (Nachvollziehbarkeit).
 * Schutz vor Aussperrung: Die letzte gültige Administration kann nicht beendet werden.
 */
final class RoleAssignments
{
    public function assign(User $user, Role $role, User $by, ?Carbon $endsAt = null, ?string $comment = null): RoleAssignment
    {
        $alreadyActive = $user->activeRoleAssignments()->where('role_id', $role->id)->exists();

        if ($alreadyActive) {
            throw new DomainException('Der Nutzer hat diese Rolle bereits.');
        }

        $assignment = $user->roleAssignments()->create([
            'role_id' => $role->id,
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'granted_by_user_id' => $by->id,
            'comment' => filled($comment) ? $comment : null,
        ]);

        AuditLog::record(AuditEvent::RoleAssigned, $by, $user, [
            'rolle' => $role->name,
            'bis' => $endsAt?->format('d.m.Y'),
            'kommentar' => filled($comment) ? $comment : null,
        ]);

        return $assignment;
    }

    public function end(RoleAssignment $assignment, ?User $by = null): void
    {
        $role = $assignment->role;

        if ($role?->key === RoleKey::Administration->value && $this->activeAdministrators($assignment->id) === 0) {
            throw new DomainException('Die letzte Administration kann nicht beendet werden.');
        }

        $assignment->forceFill(['ends_at' => now()])->save();

        AuditLog::record(AuditEvent::RoleEnded, $by, $assignment->user, ['rolle' => $role?->name]);
    }

    /** Anzahl Nutzer mit gültiger Rolle Administration und aktivem Konto, ohne die angegebene Zuordnung. */
    public function activeAdministrators(?int $exceptAssignmentId = null): int
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('activeRoleAssignments', function ($query) use ($exceptAssignmentId): void {
                $query->whereHas('role', fn ($role) => $role->where('key', RoleKey::Administration->value));

                if ($exceptAssignmentId !== null) {
                    $query->where('role_assignments.id', '!=', $exceptAssignmentId);
                }
            })
            ->count();
    }
}

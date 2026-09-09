<?php

namespace App\Modules\Administration\Actions;

use App\Modules\Administration\Exceptions\AdministrationActionRejected;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class DisableUserAction
{
    public function __construct(
        private readonly AdministrationAccess $access,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $target,
        User $actor,
        string $comment,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): User {
        if (! $this->access->canManage($actor)) {
            throw new AdministrationActionRejected(
                'Für diese Aktion fehlt die erforderliche Administrationsberechtigung.'
            );
        }

        if ($target->id === $actor->id) {
            throw new AdministrationActionRejected(
                'Das eigene Administrationskonto kann hier nicht deaktiviert werden.'
            );
        }

        $normalizedComment = trim($comment);

        if ($normalizedComment === '') {
            throw new AdministrationActionRejected(
                'Für die Deaktivierung ist eine Begründung erforderlich.'
            );
        }

        return DB::transaction(function () use (
            $target,
            $actor,
            $normalizedComment,
            $ipAddress,
            $userAgent,
        ): User {
            $lockedTarget = User::query()
                ->whereKey($target->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTarget->status !== UserStatus::Active) {
                throw new AdministrationActionRejected(
                    'Nur aktive Konten können deaktiviert werden.'
                );
            }

            $now = now();
            $oldSessionVersion = $lockedTarget->session_version;

            $lockedTarget->status = UserStatus::Disabled;
            $lockedTarget->session_version++;
            $lockedTarget->remember_token = null;
            $lockedTarget->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ACCOUNT_DISABLED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: User::class,
                subjectId: $lockedTarget->id,
                oldValues: [
                    'status' => UserStatus::Active->value,
                    'session_version' => $oldSessionVersion,
                ],
                newValues: [
                    'status' => UserStatus::Disabled->value,
                    'session_version' => $lockedTarget->session_version,
                ],
                comment: $normalizedComment,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $now,
            );

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_SESSIONS_INVALIDATED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: User::class,
                subjectId: $lockedTarget->id,
                newValues: [
                    'reason' => 'administration.account_disabled',
                ],
                comment: $normalizedComment,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $now,
            );

            return $lockedTarget;
        });
    }
}

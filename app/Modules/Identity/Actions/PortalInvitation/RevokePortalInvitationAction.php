<?php

namespace App\Modules\Identity\Actions\PortalInvitation;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RevokePortalInvitationAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(PortalInvitation $invitation, User $actor): PortalInvitation
    {
        return DB::transaction(function () use ($invitation, $actor): PortalInvitation {
            $lockedInvitation = PortalInvitation::query()
                ->lockForUpdate()
                ->findOrFail($invitation->id);

            if ($lockedInvitation->accepted_at !== null || $lockedInvitation->revoked_at !== null) {
                throw ValidationException::withMessages([
                    'invitation' => 'Diese Einladung kann nicht widerrufen werden.',
                ]);
            }

            $revokedAt = now();
            $lockedInvitation->forceFill([
                'revoked_at' => $revokedAt,
                'revoked_by_user_id' => $actor->id,
            ])->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::PORTAL_INVITATION_REVOKED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'portal_invitation',
                subjectId: $lockedInvitation->id,
                newValues: [
                    'revoked_at' => $revokedAt->toIso8601String(),
                ],
            );

            return $lockedInvitation->refresh();
        });
    }
}

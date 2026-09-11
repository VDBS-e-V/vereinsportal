<?php

namespace App\Modules\Membership\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\MembershipConsent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RevokeMembershipConsentAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        MembershipConsent $consent,
        User $actor,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): MembershipConsent {
        return DB::transaction(function () use (
            $consent,
            $actor,
            $reason,
            $ipAddress,
            $userAgent,
        ): MembershipConsent {
            $lockedConsent = MembershipConsent::query()
                ->whereKey($consent->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedConsent->revoked_at !== null) {
                throw ValidationException::withMessages([
                    'reason' => 'Diese Zustimmung wurde bereits widerrufen.',
                ]);
            }

            $revokedAt = now();
            $lockedConsent->forceFill([
                'revoked_at' => $revokedAt,
                'revoked_by_user_id' => $actor->id,
                'revocation_reason' => trim($reason),
            ])->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::MEMBERSHIP_CONSENT_REVOKED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'membership_consent',
                subjectId: $lockedConsent->id,
                newValues: [
                    'membership_id' => $lockedConsent->membership_id,
                    'consent_key' => $lockedConsent->consent_key,
                    'version' => $lockedConsent->version,
                    'revoked_at' => $revokedAt->toIso8601String(),
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $lockedConsent->refresh();
        });
    }
}

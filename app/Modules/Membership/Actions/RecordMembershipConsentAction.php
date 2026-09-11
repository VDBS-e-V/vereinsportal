<?php

namespace App\Modules\Membership\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipConsentSource;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipConsent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordMembershipConsentAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        Membership $membership,
        string $consentKey,
        string $label,
        string $version,
        MembershipConsentSource $source,
        CarbonInterface $grantedAt,
        User $actor,
        ?string $notes = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): MembershipConsent {
        $normalizedKey = strtolower(trim($consentKey));

        return DB::transaction(function () use (
            $membership,
            $normalizedKey,
            $label,
            $version,
            $source,
            $grantedAt,
            $actor,
            $notes,
            $ipAddress,
            $userAgent,
        ): MembershipConsent {
            $lockedMembership = Membership::query()
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasActiveConsent = MembershipConsent::query()
                ->where('membership_id', $lockedMembership->id)
                ->where('consent_key', $normalizedKey)
                ->whereNull('revoked_at')
                ->exists();

            if ($hasActiveConsent) {
                throw ValidationException::withMessages([
                    'consent_key' => 'Für diesen Zweck besteht bereits eine aktive Zustimmung. Bitte diese zuerst widerrufen.',
                ]);
            }

            $consent = MembershipConsent::query()->create([
                'membership_id' => $lockedMembership->id,
                'consent_key' => $normalizedKey,
                'label' => trim($label),
                'version' => trim($version),
                'source' => $source,
                'granted_at' => $grantedAt,
                'notes' => $notes !== null && trim($notes) !== ''
                    ? trim($notes)
                    : null,
                'recorded_by_user_id' => $actor->id,
            ]);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::MEMBERSHIP_CONSENT_RECORDED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'membership_consent',
                subjectId: $consent->id,
                newValues: [
                    'membership_id' => $lockedMembership->id,
                    'consent_key' => $normalizedKey,
                    'version' => $consent->version,
                    'source' => $source->value,
                    'granted_at' => $consent->granted_at->toIso8601String(),
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $consent->refresh();
        });
    }
}

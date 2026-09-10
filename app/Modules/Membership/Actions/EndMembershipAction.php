<?php

namespace App\Modules\Membership\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Support\MembershipPeriodValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class EndMembershipAction
{
    public function __construct(
        private readonly MembershipPeriodValidator $periodValidator,
        private readonly SynchronizeMembershipRoleAction $synchronizeRole,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        Membership $membership,
        string $endsOn,
        string $reason,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Membership {
        return DB::transaction(function () use (
            $membership,
            $endsOn,
            $reason,
            $actor,
            $ipAddress,
            $userAgent,
        ): Membership {
            $lockedPerson = Person::query()
                ->whereKey($membership->person_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedMembership = Membership::query()
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMembership->ends_on !== null) {
                throw ValidationException::withMessages([
                    'ends_on' => [
                        'Für diese Mitgliedschaft ist bereits ein Enddatum hinterlegt. Änderungen daran erfolgen über Bearbeiten.',
                    ],
                ]);
            }

            /** @var array{reason: string} $validatedReason */
            $validatedReason = Validator::make(
                ['reason' => trim($reason)],
                [
                    'reason' => [
                        'required',
                        'string',
                        'max:1000',
                    ],
                ],
            )->validate();

            $period = $this->periodValidator->validate([
                'starts_on' => $lockedMembership->starts_on->toDateString(),
                'ends_on' => $endsOn,
            ]);

            $this->periodValidator->ensureNoOverlap(
                $lockedPerson,
                $period,
                $lockedMembership,
            );

            $lockedMembership->ends_on = $period['ends_on'];
            $lockedMembership->save();

            $this->synchronizeRole->execute($lockedMembership);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::MEMBERSHIP_ENDED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'membership',
                subjectId: $lockedMembership->id,
                oldValues: [
                    'ends_on' => null,
                ],
                newValues: [
                    'ends_on' => $lockedMembership->ends_on?->toDateString(),
                ],
                comment: $validatedReason['reason'],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $lockedMembership->refresh();
        });
    }
}

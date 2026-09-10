<?php

namespace App\Modules\Membership\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Support\MembershipPeriodValidator;
use Carbon\Carbon;
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

            /** @var array{ends_on: string, reason: string} $validated */
            $validated = Validator::make(
                [
                    'ends_on' => $endsOn,
                    'reason' => trim($reason),
                ],
                [
                    'ends_on' => [
                        'required',
                        'date_format:Y-m-d',
                        'after_or_equal:'.$lockedMembership->starts_on->toDateString(),
                    ],
                    'reason' => [
                        'required',
                        'string',
                        'max:1000',
                    ],
                ],
            )->validate();

            $period = $this->periodValidator->validate([
                'starts_on' => $lockedMembership->starts_on->toDateString(),
                'ends_on' => $validated['ends_on'],
            ]);

            $this->periodValidator->ensureNoOverlap(
                $lockedPerson,
                $period,
                $lockedMembership,
            );

            $lockedMembership->ends_on = Carbon::parse(
                $validated['ends_on'],
            )->startOfDay();
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
                comment: $validated['reason'],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $lockedMembership->refresh();
        });
    }
}

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

final class CreateMembershipAction
{
    public function __construct(
        private readonly MembershipPeriodValidator $validator,
        private readonly SynchronizeMembershipRoleAction $synchronizeRole,
        private readonly AuditWriter $auditWriter,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function execute(
        Person $person,
        array $values,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Membership {
        return DB::transaction(function () use (
            $person,
            $values,
            $actor,
            $ipAddress,
            $userAgent,
        ): Membership {
            $lockedPerson = Person::query()
                ->whereKey($person->id)
                ->lockForUpdate()
                ->firstOrFail();

            $period = $this->validator->validate($values);
            $this->validator->ensureNoOverlap(
                $lockedPerson,
                $period,
            );

            $membership = Membership::query()->create([
                'person_id' => $lockedPerson->id,
                'starts_on' => $period['starts_on'],
                'ends_on' => $period['ends_on'],
            ]);

            $this->synchronizeRole->execute($membership);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::MEMBERSHIP_CREATED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'membership',
                subjectId: $membership->id,
                newValues: [
                    'person_id' => $lockedPerson->id,
                    'starts_on' => $membership->starts_on->toDateString(),
                    'ends_on' => $membership->ends_on?->toDateString(),
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $membership->refresh();
        });
    }
}

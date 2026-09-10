<?php

namespace App\Modules\Membership\Actions;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Support\MembershipPeriodValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateMembershipAction
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
        Membership $membership,
        array $values,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Membership {
        return DB::transaction(function () use (
            $membership,
            $values,
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

            $period = $this->validator->validate($values);

            if (
                $lockedMembership->status() === MembershipStatus::Ended
                && $lockedMembership->ends_on !== null
                && $period['ends_on'] === null
            ) {
                throw ValidationException::withMessages([
                    'ends_on' => [
                        'Eine beendete Mitgliedschaft wird nicht wieder geöffnet. Legen Sie für eine Wiederaufnahme einen neuen Mitgliedschaftszeitraum an.',
                    ],
                ]);
            }

            $this->validator->ensureNoOverlap(
                $lockedPerson,
                $period,
                $lockedMembership,
            );

            $lockedMembership->fill($period);
            $dirty = $lockedMembership->getDirty();

            if ($dirty === []) {
                return $lockedMembership->refresh();
            }

            $oldValues = [];
            $newValues = [];

            foreach (['starts_on', 'ends_on'] as $field) {
                if (! array_key_exists($field, $dirty)) {
                    continue;
                }

                $old = $lockedMembership->getRawOriginal($field);
                $new = $dirty[$field];

                $oldValues[$field] = $old === null
                    ? null
                    : (string) $old;
                $newValues[$field] = $new === null
                    ? null
                    : (string) $new;
            }

            $lockedMembership->save();
            $this->synchronizeRole->execute($lockedMembership);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::MEMBERSHIP_UPDATED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'membership',
                subjectId: $lockedMembership->id,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $lockedMembership->refresh();
        });
    }
}

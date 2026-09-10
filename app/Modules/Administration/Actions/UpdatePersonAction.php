<?php

namespace App\Modules\Administration\Actions;

use App\Modules\Administration\Support\PersonDataValidator;
use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdatePersonAction
{
    public function __construct(
        private readonly PersonDataValidator $validator,
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
    ): Person {
        return DB::transaction(function () use (
            $person,
            $values,
            $actor,
            $ipAddress,
            $userAgent,
        ): Person {
            $lockedPerson = Person::query()
                ->with('user')
                ->whereKey($person->id)
                ->lockForUpdate()
                ->firstOrFail();

            $validated = $this->validator->validate(
                $values,
                $lockedPerson,
            );

            $lockedPerson->fill($validated);

            $dirty = $lockedPerson->getDirty();

            if ($dirty === []) {
                return $lockedPerson->refresh();
            }

            $oldValues = [];
            $newValues = [];

            foreach (array_keys($dirty) as $field) {
                $old = $lockedPerson->getRawOriginal($field);
                $new = $dirty[$field];

                $oldValues[$field] = $old === null
                    ? null
                    : (string) $old;

                $newValues[$field] = $new === null
                    ? null
                    : (string) $new;
            }

            $lockedPerson->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::PERSON_UPDATED,
                actorType: AuditActorType::User,
                actorUserId: $actor->id,
                subjectType: 'person',
                subjectId: $lockedPerson->id,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            return $lockedPerson->refresh();
        });
    }
}

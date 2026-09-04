<?php

namespace App\Modules\Identity\Actions\Profile;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateProfileAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function execute(
        User $user,
        array $values,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): Person {
        $validated = Validator::make(
            $values,
            [
                'title' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'name_addition' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'birth_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'street' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                'house_number' => [
                    'nullable',
                    'string',
                    'max:30',
                ],
                'postal_code' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
                'city' => [
                    'nullable',
                    'string',
                    'max:120',
                ],
                'country_code' => [
                    'required',
                    'string',
                    'size:2',
                    'alpha',
                ],
            ],
        )->validate();

        $validated['country_code'] =
            strtoupper($validated['country_code']);

        return DB::transaction(function () use (
            $user,
            $validated,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): Person {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $person = Person::query()
                ->whereKey($lockedUser->person_id)
                ->lockForUpdate()
                ->firstOrFail();

            $person->fill($validated);

            $dirty = $person->getDirty();

            if ($dirty === []) {
                return $person;
            }

            $oldValues = [];
            $newValues = [];

            foreach (
                array_keys($dirty) as $field
            ) {
                $old = $person->getRawOriginal(
                    $field
                );

                $new = $dirty[$field];

                $oldValues[$field] =
                    $old === null
                        ? null
                        : (string) $old;

                $newValues[$field] =
                    $new === null
                        ? null
                        : (string) $new;
            }

            $person->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::PERSON_UPDATED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: 'person',
                subjectId: $person->id,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            return $person->refresh();
        });
    }
}

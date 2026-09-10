<?php

namespace App\Modules\Administration\Actions;

use App\Modules\Administration\Support\PersonDataValidator;
use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailIdentityWriteLock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdatePersonAction
{
    public function __construct(
        private readonly PersonDataValidator $validator,
        private readonly AuditWriter $auditWriter,
        private readonly EmailIdentityWriteLock $emailIdentityWriteLock,
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
        $emailCandidate = $values['email'] ?? null;

        if (! is_string($emailCandidate)) {
            throw ValidationException::withMessages([
                'email' => ['Die E-Mail-Adresse ist ungültig.'],
            ]);
        }

        return $this->emailIdentityWriteLock->execute(
            $emailCandidate,
            function () use (
                $person,
                $values,
                $actor,
                $ipAddress,
                $userAgent,
            ): Person {
                try {
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
                } catch (QueryException $exception) {
                    if ((string) $exception->getCode() === '23000') {
                        throw ValidationException::withMessages([
                            'email' => ['Diese E-Mail-Adresse ist bereits vergeben.'],
                        ]);
                    }

                    throw $exception;
                }
            },
        );
    }
}

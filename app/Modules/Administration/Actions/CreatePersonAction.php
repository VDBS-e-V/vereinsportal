<?php

namespace App\Modules\Administration\Actions;

use App\Modules\Administration\Exceptions\PossiblePersonDuplicate;
use App\Modules\Administration\Support\PersonDataValidator;
use App\Modules\Administration\Support\PersonDuplicateConfirmation;
use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Queries\FindPossiblePersonMatches;
use App\Modules\Identity\Support\EmailIdentityWriteLock;
use App\Modules\Identity\Support\PersonIdentityWriteLock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreatePersonAction
{
    public function __construct(
        private readonly PersonDataValidator $validator,
        private readonly FindPossiblePersonMatches $findPossiblePersonMatches,
        private readonly AuditWriter $auditWriter,
        private readonly EmailIdentityWriteLock $emailIdentityWriteLock,
        private readonly PersonIdentityWriteLock $personIdentityWriteLock,
        private readonly PersonDuplicateConfirmation $duplicateConfirmation,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function execute(
        array $values,
        User $actor,
        ?string $possibleDuplicateConfirmation = null,
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
                $values,
                $actor,
                $possibleDuplicateConfirmation,
                $ipAddress,
                $userAgent,
            ): Person {
                $validated = $this->validator->validate($values);

                return $this->personIdentityWriteLock->execute(
                    firstName: $validated['first_name'],
                    lastName: $validated['last_name'],
                    birthDate: $validated['birth_date'],
                    email: $validated['email'],
                    callback: function () use (
                        $validated,
                        $actor,
                        $possibleDuplicateConfirmation,
                        $ipAddress,
                        $userAgent,
                    ): Person {
                        $possibleMatches = $this->findPossiblePersonMatches->execute(
                            firstName: $validated['first_name'],
                            lastName: $validated['last_name'],
                            birthDate: $validated['birth_date'],
                            email: $validated['email'],
                        );

                        $possibleMatchIds = array_map(
                            static fn (int|string $id): int => (int) $id,
                            $possibleMatches->modelKeys(),
                        );

                        sort($possibleMatchIds, SORT_NUMERIC);

                        if (
                            $possibleMatchIds !== []
                            && ! $this->duplicateConfirmation->matches(
                                $possibleDuplicateConfirmation,
                                $validated,
                                $possibleMatchIds,
                            )
                        ) {
                            throw new PossiblePersonDuplicate(
                                $possibleMatchIds,
                                $this->duplicateConfirmation->issue(
                                    $validated,
                                    $possibleMatchIds,
                                ),
                            );
                        }

                        try {
                            return DB::transaction(function () use (
                                $validated,
                                $actor,
                                $ipAddress,
                                $userAgent,
                            ): Person {
                                $person = Person::query()->create($validated);

                                $this->auditWriter->write(
                                    eventKey: AuditEventCatalog::PERSON_CREATED,
                                    actorType: AuditActorType::User,
                                    actorUserId: $actor->id,
                                    subjectType: 'person',
                                    subjectId: $person->id,
                                    newValues: $validated,
                                    ipAddress: $ipAddress,
                                    userAgent: $userAgent,
                                );

                                return $person->refresh();
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
            },
        );
    }
}

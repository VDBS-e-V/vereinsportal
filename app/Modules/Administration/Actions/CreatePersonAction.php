<?php

namespace App\Modules\Administration\Actions;

use App\Modules\Administration\Exceptions\PossiblePersonDuplicate;
use App\Modules\Administration\Support\PersonDataValidator;
use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Queries\FindPossiblePersonMatches;
use App\Modules\Identity\Support\EmailIdentityWriteLock;
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
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function execute(
        array $values,
        User $actor,
        bool $allowPossibleDuplicate = false,
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
                $allowPossibleDuplicate,
                $ipAddress,
                $userAgent,
            ): Person {
                $validated = $this->validator->validate($values);

                if (! $allowPossibleDuplicate) {
                    $possibleMatches = $this->findPossiblePersonMatches->execute(
                        firstName: $validated['first_name'],
                        lastName: $validated['last_name'],
                        birthDate: $validated['birth_date'],
                        email: $validated['email'],
                    );

                    if ($possibleMatches->isNotEmpty()) {
                        throw new PossiblePersonDuplicate(
                            array_map(
                                static fn (int|string $id): int => (int) $id,
                                $possibleMatches->modelKeys(),
                            ),
                        );
                    }
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
    }
}

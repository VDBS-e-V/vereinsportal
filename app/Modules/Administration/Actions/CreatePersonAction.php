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
use Illuminate\Support\Facades\DB;

final class CreatePersonAction
{
    public function __construct(
        private readonly PersonDataValidator $validator,
        private readonly FindPossiblePersonMatches $findPossiblePersonMatches,
        private readonly AuditWriter $auditWriter,
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
    }
}

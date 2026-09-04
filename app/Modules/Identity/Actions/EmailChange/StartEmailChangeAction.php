<?php

namespace App\Modules\Identity\Actions\EmailChange;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\EmailChangeRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\EmailChangeCannotStart;
use App\Modules\Identity\Models\EmailChangeRequest;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class StartEmailChangeAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $user,
        string $newEmail,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): EmailChangeRequest {
        $newEmail =
            EmailNormalizer::normalize($newEmail);

        Validator::make(
            [
                'new_email' => $newEmail,
            ],
            [
                'new_email' => [
                    'required',
                    'email:rfc',
                    'max:254',
                ],
            ],
        )->validate();

        return DB::transaction(function () use (
            $user,
            $newEmail,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): EmailChangeRequest {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedUser->status
                    !== UserStatus::Active
                || $lockedUser->email_verified_at
                    === null
                || $lockedUser->person_id === null
            ) {
                throw EmailChangeCannotStart::accountUnavailable();
            }

            $person = Person::query()
                ->whereKey($lockedUser->person_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldEmail =
                EmailNormalizer::normalize(
                    $person->email
                );

            if (
                EmailNormalizer::normalize(
                    $lockedUser->email
                ) !== $oldEmail
            ) {
                throw EmailChangeCannotStart::accountUnavailable();
            }

            if ($newEmail === $oldEmail) {
                throw EmailChangeCannotStart::emailUnavailable();
            }

            $occupiedByPerson =
                Person::query()
                    ->where('email', $newEmail)
                    ->where(
                        'id',
                        '<>',
                        $person->id,
                    )
                    ->exists();

            $occupiedByUser =
                User::query()
                    ->where('email', $newEmail)
                    ->where(
                        'id',
                        '<>',
                        $lockedUser->id,
                    )
                    ->exists();

            if (
                $occupiedByPerson
                || $occupiedByUser
            ) {
                throw EmailChangeCannotStart::emailUnavailable();
            }

            $pendingRequests =
                EmailChangeRequest::query()
                    ->where(
                        'user_id',
                        $lockedUser->id,
                    )
                    ->where(
                        'status',
                        EmailChangeRequestStatus::Pending,
                    )
                    ->where(
                        'expires_at',
                        '>',
                        now(),
                    )
                    ->lockForUpdate()
                    ->get();

            foreach ($pendingRequests as $pending) {
                $pending->status =
                    EmailChangeRequestStatus::Superseded;

                $pending->superseded_at = now();
                $pending->save();

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::AUTH_EMAIL_CHANGE_SUPERSEDED,
                    actorType: AuditActorType::User,
                    actorUserId: $lockedUser->id,
                    subjectType: 'email_change_request',
                    subjectId: $pending->id,
                    newValues: [
                        'new_email' => $pending->new_email,
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceInfo: $deviceInfo,
                );
            }

            $request =
                EmailChangeRequest::query()
                    ->create([
                        'public_id' => (string) Str::ulid(),
                        'user_id' => $lockedUser->id,
                        'old_email' => $oldEmail,
                        'new_email' => $newEmail,
                        'status' => EmailChangeRequestStatus::Pending,
                        'expires_at' => now()->addDays(3),
                    ]);

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_EMAIL_CHANGE_REQUESTED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: 'user',
                subjectId: $lockedUser->id,
                newValues: [
                    'old_email' => $oldEmail,
                    'new_email' => $newEmail,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            return $request;
        });
    }
}

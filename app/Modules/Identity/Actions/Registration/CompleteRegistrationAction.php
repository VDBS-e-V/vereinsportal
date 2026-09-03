<?php

namespace App\Modules\Identity\Actions\Registration;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Roles\AssignAutomaticRoleAction;
use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\RegistrationCannotComplete;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PrivacyConsent;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Queries\FindPossiblePersonMatches;
use App\Modules\Identity\Support\EmailNormalizer;
use Illuminate\Support\Facades\DB;

final class CompleteRegistrationAction
{
    public function __construct(
        private readonly FindPossiblePersonMatches $findPossiblePersonMatches,
        private readonly AssignAutomaticRoleAction $assignAutomaticRole,
        private readonly AuditWriter $auditWriter,
    ) {
    }

    public function execute(
        string $publicId,
        int $version,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): User {
        return DB::transaction(function () use (
            $publicId,
            $version,
            $ipAddress,
            $userAgent,
            $deviceInfo,
        ): User {
            $registrationRequest = RegistrationRequest::query()
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->first();

            if ($registrationRequest === null) {
                throw RegistrationCannotComplete::invalidOrExpired();
            }

            if (
                $registrationRequest->status
                    !== RegistrationRequestStatus::PendingVerification
                || $registrationRequest->verification_version !== $version
                || $registrationRequest->verification_expires_at->isPast()
                || $registrationRequest->expires_at->isPast()
            ) {
                throw RegistrationCannotComplete::invalidOrExpired();
            }

            $email = EmailNormalizer::normalize(
                $registrationRequest->email
            );

            $possibleMatches = $this->findPossiblePersonMatches->execute(
                firstName: $registrationRequest->first_name,
                lastName: $registrationRequest->last_name,
                birthDate: $registrationRequest->birth_date,
                email: $email,
            );

            if ($possibleMatches->isNotEmpty()) {
                throw RegistrationCannotComplete::possibleDuplicate();
            }

            if (
                Person::query()->where('email', $email)->exists()
                || User::query()->where('email', $email)->exists()
            ) {
                throw RegistrationCannotComplete::emailUnavailable();
            }

            $verifiedAt = now();

            $person = Person::query()->create([
                'first_name' => $registrationRequest->first_name,
                'last_name' => $registrationRequest->last_name,
                'birth_date' => $registrationRequest->birth_date,
                'email' => $email,
                'country_code' => 'DE',
            ]);

            $user = new User();

            $user->person()->associate($person);
            $user->email = $email;
            $user->password = $registrationRequest->getRawOriginal(
                'password'
            );
            $user->status = UserStatus::Active;
            $user->email_verified_at = $verifiedAt;

            $user->save();

            PrivacyConsent::query()->create([
                'person_id' => $person->id,
                'user_id' => $user->id,
                'context' => 'registration',
                'notice_version' => $registrationRequest
                    ->privacy_notice_version,
                'accepted' => true,
                'accepted_at' => $registrationRequest->consented_at,
            ]);

            $this->assignAutomaticRole->execute(
                user: $user,
                roleKey: RoleKey::Guest,
                sourceType: 'account',
                sourceId: $user->id,
            );

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::AUTH_EMAIL_VERIFIED,
                actorType: AuditActorType::User,
                actorUserId: $user->id,
                subjectType: 'user',
                subjectId: $user->id,
                newValues: [
                    'verified_at' => $verifiedAt->toISOString(),
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ACCOUNT_REGISTERED,
                actorType: AuditActorType::User,
                actorUserId: $user->id,
                subjectType: 'user',
                subjectId: $user->id,
                newValues: [
                    'linkage_type' => 'new_person',
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceInfo: $deviceInfo,
            );

            $registrationRequest->delete();

            return $user;
        });
    }
}
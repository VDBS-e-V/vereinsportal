<?php

namespace App\Modules\Identity\Actions\Registration;

use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Exceptions\RegistrationCannotStart;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Queries\FindPossiblePersonMatches;
use App\Modules\Identity\Support\EmailNormalizer;
use App\Modules\Identity\Support\PasswordRules;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class StartRegistrationAction
{
    public function __construct(
        private readonly FindPossiblePersonMatches $findPossiblePersonMatches,
    ) {
    }

    public function execute(
        string $firstName,
        string $lastName,
        string $birthDate,
        string $email,
        string $password,
        bool $privacyAccepted,
        string $privacyNoticeVersion,
        CarbonInterface $consentedAt,
    ): RegistrationRequest {
        $email = EmailNormalizer::normalize($email);

        Validator::make([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $birthDate,
            'email' => $email,
            'password' => $password,
            'privacy_accepted' => $privacyAccepted,
            'privacy_notice_version' => $privacyNoticeVersion,
        ], [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'email' => ['required', 'email', 'max:254'],
            'password' => [
                'required',
                PasswordRules::default(),
            ],
            'privacy_accepted' => ['accepted'],
            'privacy_notice_version' => [
                'required',
                'string',
                'max:100',
            ],
        ])->validate();

        $possibleMatches = $this->findPossiblePersonMatches->execute(
            firstName: $firstName,
            lastName: $lastName,
            birthDate: $birthDate,
            email: $email,
        );

        if ($possibleMatches->isNotEmpty()) {
            throw new RegistrationCannotStart();
        }

        if (
            Person::query()->where('email', $email)->exists()
            || User::query()->where('email', $email)->exists()
        ) {
            throw new RegistrationCannotStart();
        }

        $startedAt = now();

        return RegistrationRequest::query()->create([
            'public_id' => (string) Str::ulid(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $birthDate,
            'email' => $email,
            'password' => Hash::make($password),
            'privacy_notice_version' => $privacyNoticeVersion,
            'consented_at' => $consentedAt,
            'verification_recipient_email' => $email,
            'verification_version' => 1,
            'verification_expires_at' => $startedAt->copy()->addDays(3),
            'expires_at' => $startedAt->copy()->addDays(7),
            'status' => RegistrationRequestStatus::PendingVerification,
        ]);
    }
}
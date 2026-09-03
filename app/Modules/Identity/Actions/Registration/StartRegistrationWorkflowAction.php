<?php

namespace App\Modules\Identity\Actions\Registration;

use App\Modules\Communication\Exceptions\EmailTemplateUnavailable;
use App\Modules\Identity\Actions\QueueRegistrationVerificationEmailAction;
use App\Modules\Identity\Exceptions\RegistrationVerificationEmailUnavailable;
use App\Modules\Identity\Models\RegistrationRequest;
use Carbon\CarbonInterface;

final class StartRegistrationWorkflowAction
{
    public function __construct(
        private readonly StartRegistrationAction $startRegistration,
        private readonly QueueRegistrationVerificationEmailAction $queueVerificationEmail,
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
        $registrationRequest = $this->startRegistration->execute(
            firstName: $firstName,
            lastName: $lastName,
            birthDate: $birthDate,
            email: $email,
            password: $password,
            privacyAccepted: $privacyAccepted,
            privacyNoticeVersion: $privacyNoticeVersion,
            consentedAt: $consentedAt,
        );

        try {
            $this->queueVerificationEmail->execute(
                $registrationRequest
            );
        } catch (EmailTemplateUnavailable $exception) {
            throw new RegistrationVerificationEmailUnavailable(
                registrationPublicId:
                    $registrationRequest->public_id,
                previous: $exception,
            );
        }

        return $registrationRequest;
    }
}
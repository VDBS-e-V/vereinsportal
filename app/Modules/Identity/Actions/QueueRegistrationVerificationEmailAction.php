<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Support\RegistrationVerificationUrl;
use Illuminate\Support\Facades\DB;

final class QueueRegistrationVerificationEmailAction
{
    public function __construct(
        private readonly RegistrationVerificationUrl $verificationUrl,
        private readonly QueueTemplatedEmailAction $queueTemplatedEmail,
    ) {
    }

    public function execute(
        RegistrationRequest $registrationRequest,
    ): EmailDelivery {
        return DB::transaction(
            function () use ($registrationRequest): EmailDelivery {
                $delivery = $this->queueTemplatedEmail->execute(
                    templateKey: 'auth.registration.verify',
                    recipientEmail: $registrationRequest
                        ->verification_recipient_email,
                    values: [
                        'verification_url' => $this
                            ->verificationUrl
                            ->create($registrationRequest),

                        'expires_at' => $registrationRequest
                            ->verification_expires_at
                            ->format('d.m.Y H:i \U\T\C'),

                        'first_name' => $registrationRequest
                            ->first_name,

                        'privacy_notice_version' => $registrationRequest
                            ->privacy_notice_version,
                    ],
                );

                $registrationRequest->forceFill([
                    'verification_sent_at' => now(),
                ])->save();

                return $delivery;
            }
        );
    }
}
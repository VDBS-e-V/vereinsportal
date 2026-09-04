<?php

namespace App\Modules\Identity\Actions\Registration;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Identity\Actions\QueueRegistrationVerificationEmailAction;
use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Exceptions\RegistrationVerificationCannotBeResent;
use App\Modules\Identity\Models\RegistrationRequest;
use Illuminate\Support\Facades\DB;

final class ResendRegistrationVerificationAction
{
    public function __construct(
        private readonly QueueRegistrationVerificationEmailAction $queueVerificationEmail,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        string $publicId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): EmailDelivery {
        return DB::transaction(
            function () use (
                $publicId,
                $ipAddress,
                $userAgent,
                $deviceInfo,
            ): EmailDelivery {
                $now = now();

                $registrationRequest = RegistrationRequest::query()
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->first();

                if (
                    $registrationRequest === null
                    || $registrationRequest->status
                        !== RegistrationRequestStatus::PendingVerification
                    || $registrationRequest->expires_at
                        ->lessThanOrEqualTo($now)
                ) {
                    throw RegistrationVerificationCannotBeResent::unavailable();
                }

                if (
                    $registrationRequest->verification_sent_at !== null
                    && $registrationRequest
                        ->verification_sent_at
                        ->greaterThan(
                            $now->copy()->subMinute()
                        )
                ) {
                    throw RegistrationVerificationCannotBeResent::rateLimited();
                }

                $verificationExpiresAt = $now
                    ->copy()
                    ->addDays(3);

                if (
                    $verificationExpiresAt
                        ->greaterThan(
                            $registrationRequest->expires_at
                        )
                ) {
                    $verificationExpiresAt = $registrationRequest
                        ->expires_at
                        ->copy();
                }

                $registrationRequest->forceFill([
                    'verification_version' => $registrationRequest
                        ->verification_version + 1,

                    'verification_expires_at' => $verificationExpiresAt,
                ])->save();

                /*
                 * Diese Action erzeugt Delivery +
                 * verification_sent_at atomar.
                 *
                 * Scheitert Template/Rendering, rollt die
                 * äußere Transaktion auch Version und Ablauf
                 * wieder zurück.
                 */
                $delivery = $this
                    ->queueVerificationEmail
                    ->execute($registrationRequest);

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::AUTH_VERIFICATION_RESENT,

                    actorType: AuditActorType::System,

                    actorContext: 'public_registration_verification_resend',

                    subjectType: 'registration_request',

                    subjectId: $registrationRequest->id,

                    ipAddress: $ipAddress,

                    userAgent: $userAgent,

                    deviceInfo: $deviceInfo,
                );

                return $delivery;
            }
        );
    }
}

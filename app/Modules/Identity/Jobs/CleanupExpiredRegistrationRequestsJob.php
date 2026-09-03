<?php

namespace App\Modules\Identity\Jobs;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class CleanupExpiredRegistrationRequestsJob implements
    ShouldQueue,
    ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'identity-registration-cleanup';
    }

    public function handle(
        AuditWriter $auditWriter,
    ): void {
        RegistrationRequest::query()
            ->where(
                'status',
                RegistrationRequestStatus::PendingVerification->value,
            )
            ->where(
                'expires_at',
                '<=',
                now(),
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($registrationRequests) use (
                    $auditWriter
                ): void {
                    foreach (
                        $registrationRequests
                        as $registrationRequest
                    ) {
                        $this->cleanupOne(
                            $registrationRequest->id,
                            $auditWriter,
                        );
                    }
                }
            );
    }

    private function cleanupOne(
        int $registrationRequestId,
        AuditWriter $auditWriter,
    ): void {
        DB::transaction(
            function () use (
                $registrationRequestId,
                $auditWriter
            ): void {
                $registrationRequest =
                    RegistrationRequest::query()
                        ->whereKey(
                            $registrationRequestId
                        )
                        ->lockForUpdate()
                        ->first();

                if ($registrationRequest === null) {
                    return;
                }

                if (
                    $registrationRequest->status
                    !== RegistrationRequestStatus::PendingVerification
                ) {
                    return;
                }

                if (
                    $registrationRequest
                        ->expires_at
                        ->isFuture()
                ) {
                    return;
                }

                $auditWriter->write(
                    eventKey:
                        AuditEventCatalog::
                            ACCOUNT_REGISTRATION_DELETED_UNVERIFIED,
                    actorType:
                        AuditActorType::System,
                    actorContext:
                        'registration_cleanup',
                    subjectType:
                        'registration_request',
                    subjectId:
                        $registrationRequest->id,
                    newValues: [
                        'reason' => 'expired',
                    ],
                );

                $registrationRequest->delete();
            }
        );
    }
}
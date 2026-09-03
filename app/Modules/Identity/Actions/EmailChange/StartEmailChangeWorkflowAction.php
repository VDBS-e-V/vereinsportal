<?php

namespace App\Modules\Identity\Actions\EmailChange;

use App\Modules\Identity\Models\EmailChangeRequest;
use App\Modules\Identity\Models\User;
use Throwable;

final class StartEmailChangeWorkflowAction
{
    public function __construct(
        private readonly StartEmailChangeAction $start,
        private readonly QueueEmailChangeVerificationAction $queue,
    ) {
    }

    public function execute(
        User $user,
        string $newEmail,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): EmailChangeRequest {
        /*
         * Fachliche Mutation + Audit werden zuerst committed.
         * Die Communication-Vorbereitung folgt danach.
         */
        $request = $this->start->execute(
            user: $user,
            newEmail: $newEmail,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );

        try {
            return $this->queue->execute(
                $request
            );
        } catch (Throwable $exception) {
            report($exception);

            return $request->refresh();
        }
    }
}
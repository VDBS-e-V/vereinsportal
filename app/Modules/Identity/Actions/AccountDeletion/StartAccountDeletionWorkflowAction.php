<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;

final class StartAccountDeletionWorkflowAction
{
    public function __construct(
        private readonly RequestAccountDeletionAction $requestDeletion,
        private readonly QueueAccountDeletionConfirmationEmailAction $queueConfirmationEmail,
    ) {}

    public function execute(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AccountDeletionRequest {
        $deletionRequest = $this->requestDeletion->execute(
            user: $user,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        /*
         * Bewusst nach dem fachlichen Commit.
         * Ein Mail-/Templatefehler darf den bereits erzeugten
         * Löschantrag nicht zurückrollen.
         */
        $this->queueConfirmationEmail->execute(
            $deletionRequest
        );

        return $deletionRequest->refresh();
    }
}

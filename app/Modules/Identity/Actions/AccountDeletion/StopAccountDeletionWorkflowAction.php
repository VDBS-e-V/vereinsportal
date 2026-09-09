<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Identity\Models\AccountDeletionRequest;
use Throwable;

final class StopAccountDeletionWorkflowAction
{
    public function __construct(
        private readonly StopAccountDeletionAction $stopDeletion,
        private readonly QueueAccountDeletionStoppedEmailAction $queueStoppedEmail,
    ) {}

    public function execute(
        string $publicId,
        int $actorUserId,
        string $reasonKey,
        ?string $comment = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AccountDeletionRequest {
        $deletionRequest = $this->stopDeletion->execute(
            publicId: $publicId,
            actorUserId: $actorUserId,
            reasonKey: $reasonKey,
            comment: $comment,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        /*
         * Bewusst nach dem fachlichen Commit.
         * Ein Mail-/Templatefehler darf den administrativen
         * Stop nicht zurückrollen.
         */
        try {
            $this->queueStoppedEmail->execute(
                $deletionRequest
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return $deletionRequest;
    }
}

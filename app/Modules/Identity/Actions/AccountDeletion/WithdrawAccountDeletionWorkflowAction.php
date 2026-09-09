<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Identity\Models\AccountDeletionRequest;
use Throwable;

final class WithdrawAccountDeletionWorkflowAction
{
    public function __construct(
        private readonly WithdrawAccountDeletionAction $withdrawDeletion,
        private readonly QueueAccountDeletionWithdrawnEmailAction $queueWithdrawnEmail,
    ) {}

    public function execute(
        string $publicId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AccountDeletionRequest {
        $deletionRequest = $this->withdrawDeletion->execute(
            publicId: $publicId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        /*
         * Bewusst nach dem fachlichen Commit.
         * Ein Mail-/Templatefehler darf den erfolgreichen
         * Widerruf nicht zurückrollen.
         */
        try {
            $this->queueWithdrawnEmail->execute(
                $deletionRequest
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return $deletionRequest;
    }
}

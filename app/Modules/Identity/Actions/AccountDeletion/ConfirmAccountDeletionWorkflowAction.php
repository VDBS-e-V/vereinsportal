<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Identity\Models\AccountDeletionRequest;
use Throwable;

final class ConfirmAccountDeletionWorkflowAction
{
    public function __construct(
        private readonly ConfirmAccountDeletionAction $confirmDeletion,
        private readonly QueueAccountDeletionWithdrawalAvailableEmailAction $queueWithdrawalAvailableEmail,
    ) {}

    public function execute(
        string $publicId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AccountDeletionRequest {
        $deletionRequest = $this->confirmDeletion->execute(
            publicId: $publicId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        /*
         * Bewusst nach dem fachlichen Commit.
         * Ein Mail-/Templatefehler darf die bestätigte
         * Kontolöschung nicht zurückrollen.
         */
        try {
            $this->queueWithdrawalAvailableEmail->execute(
                $deletionRequest
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return $deletionRequest;
    }
}

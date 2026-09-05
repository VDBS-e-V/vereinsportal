<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Support\AccountDeletionConfirmationUrl;
use Illuminate\Support\Facades\DB;

final class QueueAccountDeletionConfirmationEmailAction
{
    public const TEMPLATE_KEY =
        'account.deletion.confirm_request';

    public function __construct(
        private readonly QueueTemplatedEmailAction $queueTemplatedEmail,
        private readonly AccountDeletionConfirmationUrl $confirmationUrl,
    ) {}

    public function execute(
        AccountDeletionRequest $deletionRequest,
    ): void {
        DB::transaction(function () use ($deletionRequest): void {
            $request = AccountDeletionRequest::query()
                ->whereKey($deletionRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $user = $request->user()->firstOrFail();

            $expiresAt = $this->confirmationUrl->expiresAt();

            $url = $this->confirmationUrl->create(
                $request,
                $expiresAt,
            );

            $this->queueTemplatedEmail->execute(
                templateKey: self::TEMPLATE_KEY,
                recipientEmail: $user->email,
                values: [
                    'confirmation_url' => $url,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'first_name' => $user->person?->first_name,
                ],
            );

            $request->confirmation_sent_at = now();
            $request->save();
        });
    }
}

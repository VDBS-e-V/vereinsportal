<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Support\AccountDeletionWithdrawalUrl;
use LogicException;

final class QueueAccountDeletionWithdrawalAvailableEmailAction
{
    public const TEMPLATE_KEY =
        'account.deletion.withdraw_available';

    public function __construct(
        private readonly QueueTemplatedEmailAction $queueTemplatedEmail,
        private readonly AccountDeletionWithdrawalUrl $withdrawalUrl,
    ) {}

    public function execute(
        AccountDeletionRequest $deletionRequest,
    ): void {
        $request = AccountDeletionRequest::query()
            ->with('user.person')
            ->findOrFail($deletionRequest->id);

        if (
            $request->status
                !== AccountDeletionRequestStatus::PendingDeletion
            || $request->revoke_until === null
            || $request->user === null
        ) {
            throw new LogicException(
                'Account deletion withdrawal email cannot be prepared.'
            );
        }

        $values = [
            'withdraw_url' => $this->withdrawalUrl->create($request),
            'withdraw_until' => $request->revoke_until
                ->format('d.m.Y H:i'),
        ];

        $firstName = $request->user->person?->first_name;

        if ($firstName !== null) {
            $values['first_name'] = $firstName;
        }

        $this->queueTemplatedEmail->execute(
            templateKey: self::TEMPLATE_KEY,
            recipientEmail: $request->user->email,
            values: $values,
        );
    }
}

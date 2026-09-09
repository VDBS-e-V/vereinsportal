<?php

namespace App\Modules\Identity\Actions\AccountDeletion;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use LogicException;

final class QueueAccountDeletionStoppedEmailAction
{
    public const TEMPLATE_KEY =
        'account.deletion.stopped';

    public function __construct(
        private readonly QueueTemplatedEmailAction $queueTemplatedEmail,
    ) {}

    public function execute(
        AccountDeletionRequest $deletionRequest,
    ): void {
        $request = AccountDeletionRequest::query()
            ->with('user.person')
            ->findOrFail($deletionRequest->id);

        if (
            $request->status
                !== AccountDeletionRequestStatus::Stopped
            || $request->user === null
            || $request->user->status !== UserStatus::Active
        ) {
            throw new LogicException(
                'Account deletion stopped email cannot be prepared.'
            );
        }

        $supportEmail = trim(
            (string) config(
                'mail.support_address',
                '',
            )
        );

        if ($supportEmail === '') {
            throw new LogicException(
                'Account deletion stopped email requires a support address.'
            );
        }

        $values = [
            'support_email' => $supportEmail,
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

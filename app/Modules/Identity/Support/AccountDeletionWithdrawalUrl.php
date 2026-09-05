<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\AccountDeletionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use LogicException;

final class AccountDeletionWithdrawalUrl
{
    public function create(
        AccountDeletionRequest $deletionRequest,
    ): string {
        if ($deletionRequest->revoke_until === null) {
            throw new LogicException(
                'Account deletion request has no revoke deadline.'
            );
        }

        return URL::temporarySignedRoute(
            'identity.account-deletion.withdraw',
            CarbonImmutable::instance(
                $deletionRequest->revoke_until
            ),
            [
                'publicId' => $deletionRequest->public_id,
            ],
        );
    }
}

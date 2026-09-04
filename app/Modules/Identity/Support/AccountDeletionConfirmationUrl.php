<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\AccountDeletionRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;

final class AccountDeletionConfirmationUrl
{
    public function expiresAt(): CarbonImmutable
    {
        return CarbonImmutable::now()->addDays(3);
    }

    public function create(
        AccountDeletionRequest $deletionRequest,
        CarbonInterface $expiresAt,
    ): string {
        return URL::temporarySignedRoute(
            'identity.account-deletion.confirm',
            $expiresAt,
            [
                'publicId' => $deletionRequest->public_id,
            ],
        );
    }
}
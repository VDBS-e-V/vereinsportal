<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\EmailChangeRequest;
use Illuminate\Support\Facades\URL;

final class EmailChangeVerificationUrl
{
    public function create(
        EmailChangeRequest $request,
    ): string {
        return URL::temporarySignedRoute(
            name: 'identity.email-change.verify',
            expiration: $request->expires_at,
            parameters: [
                'publicId' => $request->public_id,
            ],
        );
    }
}

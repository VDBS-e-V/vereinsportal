<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\EmailChangeRequest;
use Illuminate\Support\Facades\URL;

final class EmailChangeSecurityUrl
{
    public function create(
        EmailChangeRequest $request,
    ): string {
        return URL::temporarySignedRoute(
            name: 'identity.email-change.security',
            expiration: now()->addDays(7),
            parameters: [
                'publicId' => $request->public_id,
            ],
        );
    }
}
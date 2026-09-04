<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\RegistrationRequest;
use Illuminate\Support\Facades\URL;

final class RegistrationVerificationUrl
{
    public function create(
        RegistrationRequest $registrationRequest,
    ): string {
        return URL::temporarySignedRoute(
            name: 'identity.registration.verify',
            expiration: $registrationRequest->verification_expires_at,
            parameters: [
                'publicId' => $registrationRequest->public_id,
                'version' => $registrationRequest->verification_version,
            ],
        );
    }
}

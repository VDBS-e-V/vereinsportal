<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\PortalInvitation;
use Illuminate\Support\Facades\URL;

final class PortalInvitationUrl
{
    public function create(PortalInvitation $invitation, string $token): string
    {
        return URL::temporarySignedRoute(
            name: 'identity.portal-invitation.show',
            expiration: $invitation->expires_at,
            parameters: [
                'publicId' => $invitation->public_id,
                'version' => $invitation->token_version,
                'token' => $token,
            ],
        );
    }
}

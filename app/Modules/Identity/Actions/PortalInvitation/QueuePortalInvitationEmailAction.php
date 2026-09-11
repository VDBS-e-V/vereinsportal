<?php

namespace App\Modules\Identity\Actions\PortalInvitation;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Support\PortalInvitationUrl;

final class QueuePortalInvitationEmailAction
{
    public function __construct(
        private readonly PortalInvitationUrl $invitationUrl,
        private readonly QueueTemplatedEmailAction $queueTemplatedEmail,
    ) {}

    public function execute(PortalInvitation $invitation, string $token): EmailDelivery
    {
        $invitation->loadMissing('person');

        return $this->queueTemplatedEmail->execute(
            templateKey: 'auth.portal-invitation',
            recipientEmail: $invitation->email,
            values: [
                'first_name' => $invitation->person->first_name,
                'invitation_url' => $this->invitationUrl->create($invitation, $token),
                'expires_at' => $invitation->expires_at->format('d.m.Y H:i \U\T\C'),
            ],
        );
    }
}

<?php

namespace App\Modules\Identity\Actions\PortalInvitation;

use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;

final class ResendPortalInvitationWorkflowAction
{
    public function __construct(
        private readonly ResendPortalInvitationAction $resendInvitation,
        private readonly QueuePortalInvitationEmailAction $queueEmail,
    ) {}

    public function execute(PortalInvitation $invitation, User $actor): PortalInvitation
    {
        $result = $this->resendInvitation->execute($invitation, $actor);
        $rotatedInvitation = $result['invitation'];

        $this->queueEmail->execute($rotatedInvitation, $result['token']);

        $rotatedInvitation->forceFill([
            'sent_at' => now(),
        ])->save();

        return $rotatedInvitation->refresh();
    }
}

<?php

namespace App\Modules\Identity\Actions\PortalInvitation;

use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;

final class StartPortalInvitationWorkflowAction
{
    public function __construct(
        private readonly StartPortalInvitationAction $startInvitation,
        private readonly QueuePortalInvitationEmailAction $queueEmail,
    ) {}

    public function execute(Person $person, User $actor): PortalInvitation
    {
        $result = $this->startInvitation->execute($person, $actor);
        $invitation = $result['invitation'];

        $this->queueEmail->execute($invitation, $result['token']);

        $invitation->forceFill([
            'sent_at' => now(),
        ])->save();

        return $invitation->refresh();
    }
}

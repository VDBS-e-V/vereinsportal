<?php

namespace App\Modules\Identity\Actions\EmailChange;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Identity\Models\EmailChangeRequest;
use App\Modules\Identity\Support\EmailChangeSecurityUrl;

final class QueueEmailChangeOldAddressNoticeAction
{
    public function __construct(
        private readonly QueueTemplatedEmailAction $queueEmail,
        private readonly EmailChangeSecurityUrl $securityUrl,
    ) {}

    public function execute(
        EmailChangeRequest $request,
    ): void {
        $user = $request->user()
            ->with('person')
            ->firstOrFail();

        $this->queueEmail->execute(
            templateKey: 'auth.email_change.old_address_notice',
            recipientEmail: $request->old_email,
            values: [
                'security_url' => $this->securityUrl
                    ->create($request),
                'first_name' => $user->person?->first_name
                    ?? '',
                'old_email' => $request->old_email,
                'new_email' => $request->new_email,
                'support_email' => (string) config(
                    'mail.support_address',
                    '',
                ),
            ],
        );
    }
}

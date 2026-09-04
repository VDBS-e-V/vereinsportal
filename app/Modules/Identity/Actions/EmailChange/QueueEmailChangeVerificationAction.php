<?php

namespace App\Modules\Identity\Actions\EmailChange;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Identity\Enums\EmailChangeRequestStatus;
use App\Modules\Identity\Models\EmailChangeRequest;
use App\Modules\Identity\Support\EmailChangeVerificationUrl;
use Illuminate\Support\Facades\DB;

final class QueueEmailChangeVerificationAction
{
    public function __construct(
        private readonly QueueTemplatedEmailAction $queueEmail,
        private readonly EmailChangeVerificationUrl $url,
    ) {}

    public function execute(
        EmailChangeRequest $request,
    ): EmailChangeRequest {
        return DB::transaction(
            function () use (
                $request,
            ): EmailChangeRequest {
                $locked =
                    EmailChangeRequest::query()
                        ->whereKey($request->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $locked->status
                        !== EmailChangeRequestStatus::Pending
                    || $locked->expires_at->isPast()
                ) {
                    return $locked;
                }

                $user = $locked->user()
                    ->with('person')
                    ->firstOrFail();

                $this->queueEmail->execute(
                    templateKey: 'auth.email_change.confirm_new',
                    recipientEmail: $locked->new_email,
                    values: [
                        'confirmation_url' => $this->url->create($locked),
                        'expires_at' => $locked->expires_at
                            ->format(
                                'd.m.Y H:i'
                            ),
                        'first_name' => $user->person?->first_name
                            ?? '',
                        'old_email' => $locked->old_email,
                        'new_email' => $locked->new_email,
                    ],
                );

                $locked->verification_sent_at =
                    now();

                $locked->save();

                return $locked->refresh();
            },
        );
    }
}

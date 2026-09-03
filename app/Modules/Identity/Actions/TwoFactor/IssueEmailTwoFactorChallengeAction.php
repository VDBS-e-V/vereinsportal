<?php

namespace App\Modules\Identity\Actions\TwoFactor;

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Models\TwoFactorEmailChallenge;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

final class IssueEmailTwoFactorChallengeAction
{
    public function __construct(
        private readonly TwoFactorRequirement $requirement,
        private readonly QueueTemplatedEmailAction $queueEmail,
    ) {
    }

    public function execute(
        User $user,
    ): TwoFactorEmailChallenge {
        if (
            ! $this->requirement
                ->canUseEmail($user)
        ) {
            throw TwoFactorChallengeFailed::
                unavailable();
        }

        [$challenge, $plainCode] =
            DB::transaction(
                function () use ($user): array {
                    $lockedUser = User::query()
                        ->whereKey($user->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    TwoFactorEmailChallenge::query()
                        ->where(
                            'user_id',
                            $lockedUser->id,
                        )
                        ->whereNull('used_at')
                        ->whereNull('invalidated_at')
                        ->update([
                            'invalidated_at' => now(),
                        ]);

                    $plainCode = str_pad(
                        (string) random_int(
                            0,
                            999999,
                        ),
                        6,
                        '0',
                        STR_PAD_LEFT,
                    );

                    $challenge =
                        TwoFactorEmailChallenge::query()
                            ->create([
                                'user_id' =>
                                    $lockedUser->id,
                                'code_hash' =>
                                    Hash::make(
                                        $plainCode
                                    ),
                                'expires_at' =>
                                    now()->addMinutes(15),
                            ]);

                    return [
                        $challenge,
                        $plainCode,
                    ];
                },
            );

        try {
            $user->loadMissing('person');

            $this->queueEmail->execute(
                templateKey:
                    'auth.two_factor.email_code',
                recipientEmail: $user->email,
                values: [
                    'code' => $plainCode,
                    'expires_in_minutes' => 15,
                    'first_name' =>
                        $user->person?->first_name
                        ?? '',
                    'support_email' =>
                        (string) config(
                            'mail.support_address',
                            '',
                        ),
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            throw TwoFactorChallengeFailed::
                unavailable();
        }

        DB::transaction(
            function () use ($challenge): void {
                $locked =
                    TwoFactorEmailChallenge::query()
                        ->whereKey(
                            $challenge->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $locked->used_at === null
                    && $locked->invalidated_at === null
                ) {
                    $locked->sent_at = now();
                    $locked->save();
                }
            },
        );

        return $challenge->refresh();
    }
}
<?php

use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\QueueAccountDeletionWithdrawalAvailableEmailAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('queues the withdrawal available email with the persisted revoke deadline', function () {
    Queue::fake();

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.withdraw_available')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'withdraw-available-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Widerruf möglich',
        'html' => <<<'HTML'
<a href="{{ withdraw_url }}">Widerrufen</a>
<p>Bis {{ withdraw_until }}</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $user = User::query()->create([
        'email' => 'withdraw-available@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::PendingDeletion,
        'session_version' => 2,
    ]);

    $revokeUntil = now()
        ->addDays(5)
        ->startOfSecond();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now(),
        'revoke_until' => $revokeUntil,
    ]);

    app(
        QueueAccountDeletionWithdrawalAvailableEmailAction::class
    )->execute($deletionRequest);

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        function (
            SendTemplatedEmailJob $job
        ) use ($deletionRequest, $revokeUntil): bool {
            parse_str(
                (string) parse_url(
                    $job->values['withdraw_url'],
                    PHP_URL_QUERY,
                ),
                $query,
            );

            return (int) ($query['expires'] ?? 0)
                    === $revokeUntil->timestamp
                && $job->values['withdraw_until']
                    === $revokeUntil->format('d.m.Y H:i')
                && ! array_key_exists(
                    'first_name',
                    $job->values,
                )
                && str_contains(
                    $job->values['withdraw_url'],
                    $deletionRequest->public_id,
                );
        }
    );
});

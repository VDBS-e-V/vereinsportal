<?php

use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\QueueAccountDeletionWithdrawnEmailAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('queues the withdrawn email with the normal login url', function () {
    Queue::fake();

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.withdrawn')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'withdrawn-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Kontolöschung widerrufen',
        'html' => <<<'HTML'
<a href="{{ login_url }}">Anmelden</a>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $user = User::query()->create([
        'email' => 'withdrawn@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 7,
    ]);

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::Withdrawn,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHours(2),
        'revoke_until' => now()->addDays(4),
        'withdrawn_at' => now(),
    ]);

    app(
        QueueAccountDeletionWithdrawnEmailAction::class
    )->execute($deletionRequest);

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        function (SendTemplatedEmailJob $job): bool {
            return $job->values['login_url']
                    === route('my.login')
                && ! array_key_exists(
                    'first_name',
                    $job->values,
                );
        }
    );
});

<?php

use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\QueueAccountDeletionStoppedEmailAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('queues the stopped email with support address and without unavailable first name', function () {
    Queue::fake();

    config([
        'mail.support_address' => 'support@example.test',
    ]);

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.stopped')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'stopped-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Kontolöschung gestoppt',
        'html' => '<p>Support: {{ support_email }}</p>',
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $user = User::query()->create([
        'email' => 'stopped@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 5,
    ]);

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::Stopped,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHours(2),
        'revoke_until' => now()->addDays(4),
        'stopped_at' => now(),
    ]);

    app(
        QueueAccountDeletionStoppedEmailAction::class
    )->execute($deletionRequest);

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        function (SendTemplatedEmailJob $job): bool {
            return $job->values['support_email']
                    === 'support@example.test'
                && ! array_key_exists(
                    'first_name',
                    $job->values,
                );
        }
    );
});

it('supplies the first name when the stopped account has a linked person', function () {
    Queue::fake();

    config([
        'mail.support_address' => 'support@example.test',
    ]);

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.stopped')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'stopped-person-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Hallo {{ first_name }}',
        'html' => '<p>Support: {{ support_email }}</p>',
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-01-02',
        'email' => 'stopped-person@example.test',
        'phone' => '0123456',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 5,
    ]);

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::Stopped,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHours(2),
        'revoke_until' => now()->addDays(4),
        'stopped_at' => now(),
    ]);

    app(
        QueueAccountDeletionStoppedEmailAction::class
    )->execute($deletionRequest);

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        fn (SendTemplatedEmailJob $job): bool => ($job->values['first_name'] ?? null) === 'Erika'
    );
});

it('rejects preparing the stopped email without a support address', function () {
    Queue::fake();

    config([
        'mail.support_address' => '   ',
    ]);

    $user = User::query()->create([
        'email' => 'stopped-no-support@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 5,
    ]);

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::Stopped,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHours(2),
        'revoke_until' => now()->addDays(4),
        'stopped_at' => now(),
    ]);

    expect(
        fn () => app(
            QueueAccountDeletionStoppedEmailAction::class
        )->execute($deletionRequest)
    )->toThrow(LogicException::class);

    Queue::assertNothingPushed();
});

<?php

use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\QueueAccountDeletionConfirmationEmailAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('omits an unavailable optional first name and supplies the support email', function () {
    Queue::fake();

    config([
        'mail.support_address' => 'support@example.test',
    ]);

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.confirm_request')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'confirmation-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Kontolöschung bestätigen',
        'html' => <<<'HTML'
<a href="{{ confirmation_url }}">Bestätigen</a>
<p>Gültig bis {{ expires_at }}</p>
<p>Support: {{ support_email }}</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $user = User::query()->create([
        'email' => 'confirmation-no-person@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => null,
    ]);

    app(
        QueueAccountDeletionConfirmationEmailAction::class
    )->execute($deletionRequest);

    expect($deletionRequest->refresh()->confirmation_sent_at)
        ->not->toBeNull();

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        function (SendTemplatedEmailJob $job): bool {
            return array_key_exists(
                'confirmation_url',
                $job->values,
            )
                && array_key_exists(
                    'expires_at',
                    $job->values,
                )
                && $job->values['support_email']
                    === 'support@example.test'
                && ! array_key_exists(
                    'first_name',
                    $job->values,
                );
        }
    );
});

it('supplies the first name when the account is linked to a person', function () {
    Queue::fake();

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.confirm_request')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'confirmation-person-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Hallo {{ first_name }}',
        'html' => <<<'HTML'
<a href="{{ confirmation_url }}">Bestätigen</a>
<p>Gültig bis {{ expires_at }}</p>
HTML,
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
        'email' => 'confirmation-person@example.test',
        'phone' => '0123456',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => null,
    ]);

    app(
        QueueAccountDeletionConfirmationEmailAction::class
    )->execute($deletionRequest);

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        fn (SendTemplatedEmailJob $job): bool => ($job->values['first_name'] ?? null) === 'Erika'
    );
});

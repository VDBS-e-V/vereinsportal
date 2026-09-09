<?php

use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\ConfirmAccountDeletionWorkflowAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('queues the withdrawal available email after confirming account deletion', function () {
    Queue::fake();

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.withdraw_available')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'confirm-workflow-publisher@example.test',
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
        'email' => 'confirm-workflow-success@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 4,
    ]);

    $user->email_verified_at = now();
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now()->subHour(),
        'confirmation_sent_at' => now(),
    ]);

    $confirmed = app(
        ConfirmAccountDeletionWorkflowAction::class
    )->execute($deletionRequest->public_id);

    $delivery = EmailDelivery::query()->sole();

    expect($confirmed->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($confirmed->revoke_until)
        ->not->toBeNull()
        ->and($delivery->recipient_email)
        ->toBe($user->email);
});

it('keeps a confirmed account deletion when the withdrawal email cannot be prepared', function () {
    $user = User::query()->create([
        'email' => 'confirm-workflow-mail-failure@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 4,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = 'remember-token';
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now()->subHour(),
        'confirmation_sent_at' => now(),
    ]);

    $confirmed = app(
        ConfirmAccountDeletionWorkflowAction::class
    )->execute($deletionRequest->public_id);

    expect($confirmed->status)
        ->toBe(AccountDeletionRequestStatus::PendingDeletion)
        ->and($confirmed->revoke_until)
        ->not->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::PendingDeletion)
        ->and($user->remember_token)
        ->toBeNull()
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});

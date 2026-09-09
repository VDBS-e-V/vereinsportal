<?php

use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\WithdrawAccountDeletionWorkflowAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('queues the withdrawn email after withdrawing account deletion', function () {
    Queue::fake();

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'account.deletion.withdrawn')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'withdraw-workflow-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Kontolöschung widerrufen',
        'html' => '<a href="{{ login_url }}">Anmelden</a>',
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $user = User::query()->create([
        'email' => 'withdraw-workflow-success@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::PendingDeletion,
        'session_version' => 9,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = null;
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHour(),
        'revoke_until' => now()->addDays(5),
    ]);

    $withdrawn = app(
        WithdrawAccountDeletionWorkflowAction::class
    )->execute($deletionRequest->public_id);

    $delivery = EmailDelivery::query()->sole();

    expect($withdrawn->status)
        ->toBe(AccountDeletionRequestStatus::Withdrawn)
        ->and($withdrawn->withdrawn_at)
        ->not->toBeNull()
        ->and($delivery->recipient_email)
        ->toBe($user->email);
});

it('keeps a withdrawn account deletion when the withdrawn email cannot be prepared', function () {
    $user = User::query()->create([
        'email' => 'withdraw-workflow-mail-failure@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::PendingDeletion,
        'session_version' => 9,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = null;
    $user->save();

    $deletionRequest = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHour(),
        'revoke_until' => now()->addDays(5),
    ]);

    $withdrawn = app(
        WithdrawAccountDeletionWorkflowAction::class
    )->execute($deletionRequest->public_id);

    expect($withdrawn->status)
        ->toBe(AccountDeletionRequestStatus::Withdrawn)
        ->and($withdrawn->withdrawn_at)
        ->not->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active)
        ->and($user->session_version)
        ->toBe(9)
        ->and($user->remember_token)
        ->toBeNull()
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});

<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\StopAccountDeletionWorkflowAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\AccountDeletionStopReason;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function makeStopWorkflowState(): array
{
    $user = User::query()->create([
        'email' => 'stop-workflow-user-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::PendingDeletion,
        'session_version' => 13,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = null;
    $user->save();

    $administrator = User::query()->create([
        'email' => 'stop-workflow-admin-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $role = Role::query()->create([
        'key' => RoleKey::Administration,
        'name' => 'Administration',
        'is_system' => true,
    ]);

    RoleAssignment::query()->create([
        'user_id' => $administrator->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subDay(),
    ]);

    $reason = AccountDeletionStopReason::query()->create([
        'key' => 'workflow_test_reason',
        'label' => 'Workflow-Testgrund',
        'requires_comment' => false,
        'is_active' => true,
    ]);

    $request = AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingDeletion,
        'requested_at' => now()->subDay(),
        'confirmation_sent_at' => now()->subDay(),
        'confirmed_at' => now()->subHours(2),
        'revoke_until' => now()->addDays(4),
    ]);

    return [
        $user,
        $administrator,
        $reason,
        $request,
    ];
}

it('queues the stopped email after the administrative stop commits', function () {
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
        'email' => 'stop-workflow-publisher@example.test',
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

    [
        $user,
        $administrator,
        $reason,
        $request,
    ] = makeStopWorkflowState();

    $stopped = app(
        StopAccountDeletionWorkflowAction::class
    )->execute(
        publicId: $request->public_id,
        actorUserId: $administrator->id,
        reasonKey: $reason->key,
    );

    $delivery = EmailDelivery::query()->sole();

    expect($stopped->status)
        ->toBe(AccountDeletionRequestStatus::Stopped)
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active)
        ->and($user->session_version)
        ->toBe(13)
        ->and($user->remember_token)
        ->toBeNull()
        ->and($delivery->recipient_email)
        ->toBe($user->email);
});

it('keeps the administrative stop when the stopped email cannot be prepared', function () {
    [
        $user,
        $administrator,
        $reason,
        $request,
    ] = makeStopWorkflowState();

    $stopped = app(
        StopAccountDeletionWorkflowAction::class
    )->execute(
        publicId: $request->public_id,
        actorUserId: $administrator->id,
        reasonKey: $reason->key,
    );

    expect($stopped->status)
        ->toBe(AccountDeletionRequestStatus::Stopped)
        ->and($stopped->stopped_at)
        ->not->toBeNull()
        ->and($user->refresh()->status)
        ->toBe(UserStatus::Active)
        ->and(EmailDelivery::query()->count())
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::ACCOUNT_DELETION_STOPPED,
                )
                ->count()
        )
        ->toBe(1);
});

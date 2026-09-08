<?php

use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\AccountDeletion\StartAccountDeletionWorkflowAction;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\AccountDeletionConfirmationEmailUnavailable;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function makeStartDeletionWorkflowUser(): User
{
    $user = User::query()->create([
        'email' => 'start-deletion-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('keeps the deletion request when the confirmation email is unavailable', function () {
    $user = makeStartDeletionWorkflowUser();

    try {
        app(
            StartAccountDeletionWorkflowAction::class
        )->execute($user);

        $this->fail(
            'Expected account deletion confirmation email failure.'
        );
    } catch (AccountDeletionConfirmationEmailUnavailable $exception) {
        $request = AccountDeletionRequest::query()
            ->where('public_id', $exception->deletionPublicId)
            ->sole();

        expect($request->user_id)
            ->toBe($user->id)
            ->and($request->status)
            ->toBe(AccountDeletionRequestStatus::PendingConfirmation)
            ->and($request->confirmation_sent_at)
            ->toBeNull()
            ->and(EmailDelivery::query()->count())
            ->toBe(0);
    }
});

it('prepares the confirmation email when the template is available', function () {
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
        'email' => 'start-deletion-publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Kontolöschung bestätigen',
        'html' => <<<'HTML'
<p>
    <a href="{{ confirmation_url }}">
        Kontolöschung bestätigen
    </a>
</p>
<p>Gültig bis {{ expires_at }}.</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $user = makeStartDeletionWorkflowUser();

    $request = app(
        StartAccountDeletionWorkflowAction::class
    )->execute($user);

    expect($request->status)
        ->toBe(AccountDeletionRequestStatus::PendingConfirmation)
        ->and($request->confirmation_sent_at)
        ->not->toBeNull()
        ->and(
            EmailDelivery::query()
                ->where('recipient_email', $user->email)
                ->count()
        )
        ->toBe(1);
});

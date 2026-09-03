<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\Auth\RequestPasswordResetAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Database\Seeders\PasswordResetEmailTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

function preparePasswordResetTemplateForTest(): void
{
    test()->seed(
        PasswordResetEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'auth.password.reset')
        ->sole();

    $publisher = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Passwort zurücksetzen',
        'html' => <<<'HTML'
<p>
    <a href="{{ reset_url }}">
        Passwort zurücksetzen
    </a>
</p>
<p>Gültig bis {{ expires_at }}</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);
}

function makePasswordResetRequestUser(
    array $attributes = [],
): User {
    $verifiedAt = array_key_exists(
        'email_verified_at',
        $attributes,
    )
        ? $attributes['email_verified_at']
        : now();

    unset($attributes['email_verified_at']);

    $user = User::query()->create(
        array_merge([
            'email' => 'reset-request@example.test',
            'password' => 'Sicher123!',
            'status' => UserStatus::Active,
        ], $attributes)
    );

    $user->email_verified_at = $verifiedAt;
    $user->save();

    return $user->refresh();
}

it('creates a password reset token and queues the reset email', function () {
    Queue::fake();

    preparePasswordResetTemplateForTest();

    $user = makePasswordResetRequestUser();

    app(RequestPasswordResetAction::class)->execute(
        email: '  RESET-REQUEST@EXAMPLE.TEST  ',
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Browser',
    );

    expect(
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->exists()
    )
        ->toBeTrue()
        ->and(
            EmailDelivery::query()
                ->where(
                    'recipient_email',
                    $user->email,
                )
                ->count()
        )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::
                        AUTH_PASSWORD_RESET_REQUESTED,
                )
                ->count()
        )
        ->toBe(1);
});

it('does not reveal or persist a reset token for an unknown email', function () {
    Queue::fake();

    preparePasswordResetTemplateForTest();

    app(RequestPasswordResetAction::class)->execute(
        email: 'unknown@example.test',
        ipAddress: '127.0.0.1',
    );

    expect(
        DB::table('password_reset_tokens')
            ->count()
    )
        ->toBe(0)
        ->and(EmailDelivery::query()->count())
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::
                        AUTH_PASSWORD_RESET_REQUESTED,
                )
                ->count()
        )
        ->toBe(1);
});

it('does not send a reset email for an unusable account', function (
    UserStatus $status,
) {
    Queue::fake();

    preparePasswordResetTemplateForTest();

    makePasswordResetRequestUser([
        'status' => $status,
    ]);

    app(RequestPasswordResetAction::class)->execute(
        email: 'reset-request@example.test',
    );

    expect(
        DB::table('password_reset_tokens')
            ->count()
    )
        ->toBe(0)
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
})->with([
    UserStatus::PendingVerification,
    UserStatus::Disabled,
    UserStatus::PendingDeletion,
    UserStatus::Anonymized,
]);

it('does not send a reset email for an unverified account', function () {
    Queue::fake();

    preparePasswordResetTemplateForTest();

    makePasswordResetRequestUser([
        'email_verified_at' => null,
    ]);

    app(RequestPasswordResetAction::class)->execute(
        email: 'reset-request@example.test',
    );

    expect(
        DB::table('password_reset_tokens')
            ->count()
    )
        ->toBe(0)
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});

it('rolls back the reset token when the email cannot be prepared', function () {
    $user = makePasswordResetRequestUser();

    app(RequestPasswordResetAction::class)->execute(
        email: $user->email,
    );

    expect(
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->exists()
    )
        ->toBeFalse()
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});

it('never stores password reset secrets in audit values', function () {
    Queue::fake();

    preparePasswordResetTemplateForTest();

    $user = makePasswordResetRequestUser();

    app(RequestPasswordResetAction::class)->execute(
        email: $user->email,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Browser',
    );

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::
                AUTH_PASSWORD_RESET_REQUESTED,
        )
        ->sole();

    $serialized = json_encode([
        $audit->old_values,
        $audit->new_values,
    ]);

    expect($serialized)
        ->not->toContain('token')
        ->and($serialized)
        ->not->toContain('password');
});
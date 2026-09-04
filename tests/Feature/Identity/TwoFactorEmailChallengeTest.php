<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\TwoFactor\IssueEmailTwoFactorChallengeAction;
use App\Modules\Identity\Actions\TwoFactor\VerifyEmailTwoFactorChallengeAction;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\TwoFactorChallengeFailed;
use App\Modules\Identity\Models\TwoFactorEmailChallenge;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRateLimiter;
use Database\Seeders\TwoFactorEmailCodeTemplateSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

function makeEmailTwoFactorUser(): User
{
    $user = User::query()->create([
        'email' => fake()
            ->unique()
            ->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $user->email_verified_at = now();
    $user->save();

    TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Email,
        'confirmed_at' => now(),
    ]);

    return $user->refresh();
}

function publishTwoFactorEmailTemplate(): void
{
    test()->seed(
        TwoFactorEmailCodeTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where(
            'key',
            'auth.two_factor.email_code',
        )
        ->sole();

    $publisher = User::query()->create([
        'email' => fake()
            ->unique()
            ->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Ihr Sicherheitscode',
        'html' => <<<'HTML'
<p>Code: {{ code }}</p>
<p>{{ expires_in_minutes }} Minuten</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);
}

it('issues a hashed email challenge valid for fifteen minutes', function () {
    Queue::fake();

    publishTwoFactorEmailTemplate();

    $user = makeEmailTwoFactorUser();

    $challenge = app(
        IssueEmailTwoFactorChallengeAction::class
    )->execute($user);

    expect($challenge->sent_at)
        ->not->toBeNull()
        ->and(
            $challenge->expires_at
                ->greaterThan(
                    now()->addMinutes(14)
                )
        )
        ->toBeTrue()
        ->and(
            $challenge->expires_at
                ->lessThan(
                    now()->addMinutes(16)
                )
        )
        ->toBeTrue()
        ->and($challenge->code_hash)
        ->not->toMatch('/^\d{6}$/')
        ->and(
            EmailDelivery::query()
                ->where(
                    'recipient_email',
                    $user->email,
                )
                ->count()
        )
        ->toBe(1);
});

it('invalidates the previous open email challenge', function () {
    Queue::fake();

    publishTwoFactorEmailTemplate();

    $user = makeEmailTwoFactorUser();

    $action = app(
        IssueEmailTwoFactorChallengeAction::class
    );

    $first = $action->execute($user);
    $second = $action->execute($user);

    $first->refresh();
    $second->refresh();

    expect($first->invalidated_at)
        ->not->toBeNull()
        ->and($second->invalidated_at)
        ->toBeNull()
        ->and($second->used_at)
        ->toBeNull();
});

it('accepts a correct email code only once', function () {
    $user = makeEmailTwoFactorUser();

    $challenge =
        TwoFactorEmailChallenge::query()
            ->create([
                'user_id' => $user->id,
                'code_hash' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(15),
                'sent_at' => now(),
            ]);

    $action = app(
        VerifyEmailTwoFactorChallengeAction::class
    );

    $action->execute(
        user: $user,
        code: '123456',
        ipAddress: '192.0.2.10',
    );

    $challenge->refresh();

    expect($challenge->used_at)
        ->not->toBeNull();

    expect(
        fn () => $action->execute(
            user: $user,
            code: '123456',
            ipAddress: '192.0.2.10',
        )
    )->toThrow(
        TwoFactorChallengeFailed::class
    );
});

it('rejects an expired email code', function () {
    $user = makeEmailTwoFactorUser();

    TwoFactorEmailChallenge::query()
        ->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subSecond(),
            'sent_at' => now()
                ->subMinutes(16),
        ]);

    expect(
        fn () => app(
            VerifyEmailTwoFactorChallengeAction::class
        )->execute(
            user: $user,
            code: '123456',
            ipAddress: '192.0.2.11',
        )
    )->toThrow(
        TwoFactorChallengeFailed::class
    );
});

it('locks the user after five wrong two factor codes', function () {
    $user = makeEmailTwoFactorUser();

    TwoFactorEmailChallenge::query()
        ->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
            'sent_at' => now(),
        ]);

    $action = app(
        VerifyEmailTwoFactorChallengeAction::class
    );

    foreach (range(1, 5) as $attempt) {
        try {
            $action->execute(
                user: $user,
                code: '999999',
                ipAddress: '192.0.2.12',
            );
        } catch (
            TwoFactorChallengeFailed
        ) {
        }
    }

    expect(
        app(TwoFactorRateLimiter::class)
            ->tooManyUserAttempts(
                $user->id
            )
    )
        ->toBeTrue()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_2FA_CHALLENGE_FAILED,
                )
                ->count()
        )
        ->toBe(5);
});

it('locks an ip after twenty five two factor failures', function () {
    $limiter = app(
        TwoFactorRateLimiter::class
    );

    $ip = '192.0.2.25';

    foreach (range(1, 25) as $userId) {
        $limiter->hitFailure(
            userId: 10_000 + $userId,
            ipAddress: $ip,
        );
    }

    expect(
        $limiter->tooManyIpAttempts(
            $ip
        )
    )->toBeTrue();
});

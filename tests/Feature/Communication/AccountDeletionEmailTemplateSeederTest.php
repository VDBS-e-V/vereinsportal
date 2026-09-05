<?php

use App\Modules\Communication\Models\EmailTemplate;
use Database\Seeders\AccountDeletionEmailTemplateSeeder;
use Database\Seeders\DatabaseSeeder;

it('seeds the fail closed account deletion email template catalog', function () {
    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $templates = EmailTemplate::query()
        ->whereIn('key', [
            'account.deletion.confirm_request',
            'account.deletion.withdraw_available',
            'account.deletion.withdrawn',
            'account.deletion.stopped',
        ])
        ->get()
        ->keyBy('key');

    expect($templates->keys()->sort()->values()->all())
        ->toBe([
            'account.deletion.confirm_request',
            'account.deletion.stopped',
            'account.deletion.withdraw_available',
            'account.deletion.withdrawn',
        ]);

    foreach ($templates as $template) {
        expect($template->is_active)
            ->toBeFalse()
            ->and($template->updated_by_user_id)
            ->toBeNull()
            ->and($template->versions()->count())
            ->toBe(0);
    }

    $confirm = $templates['account.deletion.confirm_request']
        ->placeholders()
        ->get()
        ->keyBy('key');

    expect($confirm->keys()->sort()->values()->all())
        ->toBe([
            'confirmation_url',
            'expires_at',
            'first_name',
            'support_email',
        ])
        ->and($confirm['confirmation_url']->is_required)
        ->toBeTrue()
        ->and($confirm['expires_at']->is_required)
        ->toBeTrue()
        ->and($confirm['first_name']->is_required)
        ->toBeFalse()
        ->and($confirm['support_email']->is_required)
        ->toBeFalse();

    $withdrawAvailable = $templates['account.deletion.withdraw_available']
        ->placeholders()
        ->get()
        ->keyBy('key');

    expect($withdrawAvailable->keys()->sort()->values()->all())
        ->toBe([
            'first_name',
            'withdraw_until',
            'withdraw_url',
        ])
        ->and($withdrawAvailable['withdraw_url']->is_required)
        ->toBeTrue()
        ->and($withdrawAvailable['withdraw_until']->is_required)
        ->toBeTrue()
        ->and($withdrawAvailable['first_name']->is_required)
        ->toBeFalse();

    $withdrawn = $templates['account.deletion.withdrawn']
        ->placeholders()
        ->get()
        ->keyBy('key');

    expect($withdrawn->keys()->sort()->values()->all())
        ->toBe([
            'first_name',
            'login_url',
        ])
        ->and($withdrawn['login_url']->is_required)
        ->toBeTrue()
        ->and($withdrawn['first_name']->is_required)
        ->toBeFalse();

    $stopped = $templates['account.deletion.stopped']
        ->placeholders()
        ->get()
        ->keyBy('key');

    expect($stopped->keys()->sort()->values()->all())
        ->toBe([
            'first_name',
            'support_email',
        ])
        ->and($stopped['support_email']->is_required)
        ->toBeTrue()
        ->and($stopped['first_name']->is_required)
        ->toBeFalse();
});

it('seeds the account deletion email catalog idempotently', function () {
    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    $this->seed(
        AccountDeletionEmailTemplateSeeder::class
    );

    expect(
        EmailTemplate::query()
            ->where('key', 'like', 'account.deletion.%')
            ->count()
    )->toBe(4);
});

it('includes all system email template seeders in the database seeder', function () {
    $this->seed(
        DatabaseSeeder::class
    );

    $keys = EmailTemplate::query()
        ->orderBy('key')
        ->pluck('key')
        ->all();

    expect($keys)->toBe([
        'account.deletion.confirm_request',
        'account.deletion.stopped',
        'account.deletion.withdraw_available',
        'account.deletion.withdrawn',
        'auth.email_change.confirm_new',
        'auth.email_change.old_address_notice',
        'auth.password_reset',
        'auth.registration.verify',
        'auth.two_factor.email_code',
    ]);
});

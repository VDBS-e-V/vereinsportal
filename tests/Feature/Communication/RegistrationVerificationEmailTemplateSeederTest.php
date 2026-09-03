<?php

use App\Modules\Communication\Models\EmailTemplate;
use Database\Seeders\RegistrationVerificationEmailTemplateSeeder;

it('seeds the registration verification email template catalog', function () {
    $this->seed(
        RegistrationVerificationEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'auth.registration.verify')
        ->sole();

    expect($template->name)
        ->toBe('Registrierung bestätigen')
        ->and($template->is_active)
        ->toBeFalse()
        ->and($template->updated_by_user_id)
        ->toBeNull()
        ->and($template->versions()->count())
        ->toBe(0);

    $placeholders = $template
        ->placeholders()
        ->orderBy('key')
        ->get()
        ->keyBy('key');

    expect($placeholders->keys()->all())
        ->toBe([
            'expires_at',
            'first_name',
            'privacy_notice_version',
            'support_email',
            'verification_url',
        ])
        ->and($placeholders['verification_url']->is_required)
        ->toBeTrue()
        ->and($placeholders['expires_at']->is_required)
        ->toBeTrue()
        ->and($placeholders['first_name']->is_required)
        ->toBeFalse()
        ->and($placeholders['privacy_notice_version']->is_required)
        ->toBeFalse()
        ->and($placeholders['support_email']->is_required)
        ->toBeFalse();
});

it('seeds the registration verification template idempotently', function () {
    $this->seed(
        RegistrationVerificationEmailTemplateSeeder::class
    );

    $this->seed(
        RegistrationVerificationEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'auth.registration.verify')
        ->sole();

    expect(
        EmailTemplate::query()
            ->where('key', 'auth.registration.verify')
            ->count()
    )->toBe(1)
        ->and($template->placeholders()->count())
        ->toBe(5)
        ->and($template->versions()->count())
        ->toBe(0);
});
<?php

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Database\QueryException;

it('stores placeholder metadata for an email template', function () {
    $user = User::query()->create([
        'email' => 'admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Registrierung bestätigen',
        'draft_html' => '<p>{{ verification_url }}</p>',
        'updated_by_user_id' => $user->id,
    ]);

    $placeholder = EmailTemplatePlaceholder::query()->create([
        'email_template_id' => $template->id,
        'key' => 'verification_url',
        'label' => 'Verifikationslink',
        'description' => 'Link zum Abschluss der Registrierung.',
        'example_value' => 'https://my.vdb.test/verify/example',
        'is_required' => true,
        'is_active' => true,
    ]);

    expect($placeholder->key)
        ->toBe('verification_url')
        ->and($placeholder->is_required)
        ->toBeTrue()
        ->and($placeholder->is_active)
        ->toBeTrue()
        ->and($placeholder->template->is($template))
        ->toBeTrue()
        ->and($template->placeholders()->count())
        ->toBe(1);
});

it('prevents duplicate placeholder keys within the same template', function () {
    $user = User::query()->create([
        'email' => 'admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Registrierung bestätigen',
        'draft_html' => '<p>{{ verification_url }}</p>',
        'updated_by_user_id' => $user->id,
    ]);

    $attributes = [
        'email_template_id' => $template->id,
        'key' => 'verification_url',
        'label' => 'Verifikationslink',
        'description' => 'Link zum Abschluss.',
        'example_value' => null,
        'is_required' => true,
        'is_active' => true,
    ];

    EmailTemplatePlaceholder::query()->create($attributes);

    expect(
        fn () => EmailTemplatePlaceholder::query()->create($attributes)
    )->toThrow(QueryException::class);
});

it('allows the same placeholder key on different templates', function () {
    $user = User::query()->create([
        'email' => 'admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $firstTemplate = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Registrierung',
        'draft_html' => '<p>{{ first_name }}</p>',
        'updated_by_user_id' => $user->id,
    ]);

    $secondTemplate = EmailTemplate::query()->create([
        'key' => 'auth.password_reset',
        'name' => 'Passwort zurücksetzen',
        'is_active' => true,
        'draft_subject' => 'Passwort zurücksetzen',
        'draft_html' => '<p>{{ first_name }}</p>',
        'updated_by_user_id' => $user->id,
    ]);

    foreach ([$firstTemplate, $secondTemplate] as $template) {
        EmailTemplatePlaceholder::query()->create([
            'email_template_id' => $template->id,
            'key' => 'first_name',
            'label' => 'Vorname',
            'description' => 'Vorname der empfangenden Person.',
            'example_value' => 'Erika',
            'is_required' => false,
            'is_active' => true,
        ]);
    }

    expect(
        EmailTemplatePlaceholder::query()
            ->where('key', 'first_name')
            ->count()
    )->toBe(2);
});
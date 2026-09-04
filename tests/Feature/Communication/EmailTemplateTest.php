<?php

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Database\QueryException;

it('stores an editable email template draft', function () {
    $user = User::query()->create([
        'email' => 'admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Bitte Registrierung bestätigen',
        'draft_html' => '<p>Bitte bestätigen.</p>',
        'updated_by_user_id' => $user->id,
    ]);

    expect($template->key)
        ->toBe('auth.registration.verify')
        ->and($template->is_active)
        ->toBeTrue()
        ->and($template->updatedBy->is($user))
        ->toBeTrue();
});

it('stores immutable numbered published template versions', function () {
    $user = User::query()->create([
        'email' => 'publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Entwurf',
        'draft_html' => '<p>Entwurf</p>',
        'updated_by_user_id' => $user->id,
    ]);

    $version = EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Bitte Registrierung bestätigen',
        'html' => '<p>{{ verification_url }}</p>',
        'published_by_user_id' => $user->id,
        'published_at' => now(),
    ]);

    expect($version->version)
        ->toBe(1)
        ->and($version->template->is($template))
        ->toBeTrue()
        ->and($version->publishedBy->is($user))
        ->toBeTrue()
        ->and($version->published_at)
        ->not->toBeNull();
});

it('prevents duplicate version numbers for the same template', function () {
    $user = User::query()->create([
        'email' => 'publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Entwurf',
        'draft_html' => '<p>Entwurf</p>',
        'updated_by_user_id' => $user->id,
    ]);

    $attributes = [
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Version 1',
        'html' => '<p>Version 1</p>',
        'published_by_user_id' => $user->id,
        'published_at' => now(),
    ];

    EmailTemplateVersion::query()->create($attributes);

    expect(
        fn () => EmailTemplateVersion::query()->create($attributes)
    )->toThrow(QueryException::class);
});

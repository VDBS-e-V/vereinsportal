<?php

use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Schema;

it('stores a system email delivery without a sender', function () {
    $delivery = EmailDelivery::query()->create([
        'template_version_id' => null,
        'sender_user_id' => null,
        'recipient_email' => 'recipient@example.test',
        'subject' => 'Testnachricht',
        'delivery_type' => 'system',
        'status' => 'queued',
        'attempts' => 0,
        'queued_at' => now(),
        'sent_at' => null,
        'failed_at' => null,
        'last_error_class' => null,
    ]);

    expect($delivery->recipient_email)
        ->toBe('recipient@example.test')
        ->and($delivery->attempts)
        ->toBe(0)
        ->and($delivery->queued_at)
        ->not->toBeNull()
        ->and($delivery->sender)
        ->toBeNull()
        ->and($delivery->templateVersion)
        ->toBeNull();
});

it('links a delivery to its template version and sender when present', function () {
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
        'draft_html' => '<p>Entwurf</p>',
        'updated_by_user_id' => $user->id,
    ]);

    $version = EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Registrierung bestätigen',
        'html' => '<p>{{ verification_url }}</p>',
        'published_by_user_id' => $user->id,
        'published_at' => now(),
    ]);

    $delivery = EmailDelivery::query()->create([
        'template_version_id' => $version->id,
        'sender_user_id' => $user->id,
        'recipient_email' => 'recipient@example.test',
        'subject' => $version->subject,
        'delivery_type' => 'manual',
        'status' => 'queued',
        'attempts' => 0,
        'queued_at' => now(),
    ]);

    expect($delivery->templateVersion->is($version))
        ->toBeTrue()
        ->and($delivery->sender->is($user))
        ->toBeTrue()
        ->and($version->deliveries()->count())
        ->toBe(1);
});

it('does not persist a duplicated email html body', function () {
    expect(Schema::hasColumn(
        'email_deliveries',
        'html',
    ))->toBeFalse()
        ->and(Schema::hasColumn(
            'email_deliveries',
            'body',
        ))->toBeFalse();
});
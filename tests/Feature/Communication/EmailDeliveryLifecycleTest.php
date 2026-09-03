<?php

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Communication\Models\EmailDelivery;

it('casts email delivery lifecycle values to enums', function () {
    $delivery = EmailDelivery::query()->create([
        'template_version_id' => null,
        'sender_user_id' => null,
        'recipient_email' => 'recipient@example.test',
        'subject' => 'Test',
        'delivery_type' => EmailDeliveryType::System,
        'status' => EmailDeliveryStatus::Queued,
        'attempts' => 0,
        'queued_at' => now(),
    ]);

    $delivery->refresh();

    expect($delivery->delivery_type)
        ->toBe(EmailDeliveryType::System)
        ->and($delivery->status)
        ->toBe(EmailDeliveryStatus::Queued);
});

it('defines the initial communication delivery values', function () {
    expect(array_map(
        fn (EmailDeliveryType $type): string => $type->value,
        EmailDeliveryType::cases(),
    ))->toBe([
        'system',
        'manual',
        'test',
    ]);

    expect(array_map(
        fn (EmailDeliveryStatus $status): string => $status->value,
        EmailDeliveryStatus::cases(),
    ))->toBe([
        'queued',
        'sent',
        'failed',
    ]);
});
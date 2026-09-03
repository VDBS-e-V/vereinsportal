<?php

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('writes only whitelisted values with three year retention', function () {
    $occurredAt = CarbonImmutable::parse(
        '2026-08-31 12:00:00',
        'UTC',
    );

    $event = (new AuditWriter())->write(
        eventKey: AuditEventCatalog::ROLE_AUTOMATIC_ASSIGNED,
        actorType: AuditActorType::System,
        actorContext: 'registration',
        subjectType: 'role_assignment',
        subjectId: 123,
        newValues: [
            'role' => 'guest',
            'source' => 'automatic',
            'password' => 'must-never-be-audited',
        ],
        occurredAt: $occurredAt,
    );

    expect($event->actor_type)
        ->toBe(AuditActorType::System)
        ->and($event->new_values)
        ->toBe([
            'role' => 'guest',
            'source' => 'automatic',
        ])
        ->and($event->retention_until->toDateTimeString())
        ->toBe('2029-08-31 12:00:00');
});

it('rejects an audit event without an explicit value whitelist', function () {
    expect(fn () => (new AuditWriter())->write(
        eventKey: 'unconfigured.event',
        actorType: AuditActorType::System,
        newValues: [
            'anything' => 'value',
        ],
    ))->toThrow(\InvalidArgumentException::class);

    expect(AuditEvent::query()->count())->toBe(0);
});

it('participates in the callers database transaction', function () {
    $writer = new AuditWriter();

    expect(fn () => DB::transaction(function () use ($writer): void {
        $writer->write(
            eventKey: AuditEventCatalog::ROLE_AUTOMATIC_ASSIGNED,
            actorType: AuditActorType::System,
            newValues: [
                'role' => 'guest',
                'source' => 'automatic',
            ],
        );

        throw new \RuntimeException('force rollback');
    }))->toThrow(\RuntimeException::class, 'force rollback');

    expect(AuditEvent::query()->count())->toBe(0);
});

it('writes verification resend audit without secret values', function () {
    $event = app(
        \App\Modules\Audit\Services\AuditWriter::class
    )->write(
        eventKey: \App\Modules\Audit\Support\AuditEventCatalog::AUTH_VERIFICATION_RESENT,
        actorType: \App\Modules\Audit\Enums\AuditActorType::System,
        actorContext: 'public_registration_verification_resend',
        subjectType: 'registration_request',
        subjectId: 123,
        newValues: [
            'verification_url' => 'must-not-be-stored',
            'verification_version' => 42,
        ],
    );

    expect($event->event_key)
        ->toBe('auth.verification.resent')
        ->and($event->new_values)
        ->toBeNull();
});
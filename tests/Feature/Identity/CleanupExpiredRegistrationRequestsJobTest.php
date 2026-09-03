<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Identity\Enums\RegistrationRequestStatus;
use App\Modules\Identity\Jobs\CleanupExpiredRegistrationRequestsJob;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\RegistrationRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function makeRegistrationRequestForCleanupTest(
    array $attributes = [],
): RegistrationRequest {
    return RegistrationRequest::query()->create(
        array_merge(
            [
                'public_id' => (string) Str::ulid(),
                'first_name' => 'Erika',
                'last_name' => 'Mustermann',
                'birth_date' => '1990-05-10',
                'email' =>
                    fake()->unique()->safeEmail(),
                'password' => Hash::make(
                    'Sicher123!'
                ),
                'privacy_notice_version' =>
                    '2026-09-01T21:04:00Z',
                'consented_at' => now(),
                'verification_recipient_email' =>
                    fake()->unique()->safeEmail(),
                'verification_version' => 1,
                'verification_expires_at' =>
                    now()->subHour(),
                'verification_sent_at' =>
                    now()->subDays(3),
                'expires_at' =>
                    now()->subMinute(),
                'status' =>
                    RegistrationRequestStatus::
                        PendingVerification,
            ],
            $attributes,
        )
    );
}

it('hard deletes expired unverified registration requests with audit', function () {
    $expired =
        makeRegistrationRequestForCleanupTest();

    $active =
        makeRegistrationRequestForCleanupTest([
            'email' => 'active@example.test',
            'verification_recipient_email' =>
                'active@example.test',
            'verification_expires_at' =>
                now()->addDay(),
            'expires_at' =>
                now()->addDays(4),
        ]);

    $job =
        new CleanupExpiredRegistrationRequestsJob();

    $job->handle(
        app(AuditWriter::class)
    );

    expect(
        RegistrationRequest::query()
            ->find($expired->id)
    )->toBeNull();

    expect(
        RegistrationRequest::query()
            ->find($active->id)
    )->not->toBeNull();

    $auditEvent = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::
                ACCOUNT_REGISTRATION_DELETED_UNVERIFIED,
        )
        ->sole();

    expect($auditEvent->subject_type)
        ->toBe('registration_request')
        ->and(
            (string) $auditEvent->subject_id
        )
        ->toBe(
            (string) $expired->id
        )
        ->and($auditEvent->new_values)
        ->toBe([
            'reason' => 'expired',
        ]);
});

it('is idempotent when registration cleanup runs more than once', function () {
    makeRegistrationRequestForCleanupTest();

    $job =
        new CleanupExpiredRegistrationRequestsJob();

    $job->handle(
        app(AuditWriter::class)
    );

    $job->handle(
        app(AuditWriter::class)
    );

    expect(
        RegistrationRequest::query()->count()
    )->toBe(0);

    expect(
        AuditEvent::query()
            ->where(
                'event_key',
                AuditEventCatalog::
                    ACCOUNT_REGISTRATION_DELETED_UNVERIFIED,
            )
            ->count()
    )->toBe(1);
});

it('never deletes an existing person while cleaning an expired registration', function () {
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-05-10',
        'email' => 'existing@example.test',
    ]);

    makeRegistrationRequestForCleanupTest([
        'email' => 'pending@example.test',
        'verification_recipient_email' =>
            'pending@example.test',
    ]);

    $job =
        new CleanupExpiredRegistrationRequestsJob();

    $job->handle(
        app(AuditWriter::class)
    );

    expect(
        RegistrationRequest::query()->count()
    )->toBe(0)
        ->and(
            Person::query()
                ->find($person->id)
        )
        ->not->toBeNull();
});

it('does not delete a registration before its overall expiry', function () {
    $registrationRequest =
        makeRegistrationRequestForCleanupTest([
            /*
             * Der einzelne Verifikationslink darf
             * abgelaufen sein, während der gesamte
             * Registrierungsvorgang weiterhin lebt.
             */
            'verification_expires_at' =>
                now()->subMinute(),
            'expires_at' =>
                now()->addDays(2),
        ]);

    $job =
        new CleanupExpiredRegistrationRequestsJob();

    $job->handle(
        app(AuditWriter::class)
    );

    expect(
        RegistrationRequest::query()
            ->find($registrationRequest->id)
    )->not->toBeNull();

    expect(
        AuditEvent::query()
            ->where(
                'event_key',
                AuditEventCatalog::
                    ACCOUNT_REGISTRATION_DELETED_UNVERIFIED,
            )
            ->count()
    )->toBe(0);
});
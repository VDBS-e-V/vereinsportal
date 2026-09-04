<?php

use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

it('stores the audit event structure with typed values', function () {
    $event = AuditEvent::query()->create([
        'occurred_at' => now(),
        'event_key' => 'role.automatic_assigned',
        'actor_type' => 'system',
        'actor_context' => 'registration',
        'subject_type' => 'role_assignment',
        'subject_id' => 123,
        'new_values' => [
            'role' => 'guest',
            'source' => 'automatic',
        ],
        'retention_until' => now()->addYears(3),
    ]);

    expect($event->occurred_at)
        ->toBeInstanceOf(Carbon::class)
        ->and($event->new_values)
        ->toBe([
            'role' => 'guest',
            'source' => 'automatic',
        ])
        ->and($event->retention_until)
        ->toBeInstanceOf(Carbon::class)
        ->and($event->created_at)
        ->toBeInstanceOf(Carbon::class);
});

it('has no updated at column because audit records are immutable', function () {
    expect(Schema::hasColumn('audit_events', 'updated_at'))
        ->toBeFalse();
});

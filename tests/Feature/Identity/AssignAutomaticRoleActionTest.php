<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Roles\AssignAutomaticRoleAction;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('assigns an automatic role and audits it atomically', function () {
    $user = User::query()->create([
        'email' => 'automatic-role@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $assignment = app(AssignAutomaticRoleAction::class)->execute(
        user: $user,
        roleKey: RoleKey::Guest,
        sourceType: 'account',
        sourceId: $user->id,
    );

    expect($assignment->source)
        ->toBe(RoleAssignmentSource::Automatic)
        ->and($assignment->ends_at)
        ->toBeNull()
        ->and($assignment->source_type)
        ->toBe('account')
        ->and($assignment->source_id)
        ->toBe($user->id);

    $audit = AuditEvent::query()->sole();

    expect($audit->event_key)
        ->toBe(AuditEventCatalog::ROLE_AUTOMATIC_ASSIGNED)
        ->and($audit->subject_type)
        ->toBe('role_assignment')
        ->and($audit->subject_id)
        ->toBe($assignment->id)
        ->and($audit->new_values)
        ->toBe([
            'role' => 'guest',
            'source' => 'automatic',
        ]);
});

it('does not create a second active identical assignment', function () {
    $user = User::query()->create([
        'email' => 'idempotent-role@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $action = app(AssignAutomaticRoleAction::class);

    $first = $action->execute(
        user: $user,
        roleKey: RoleKey::Guest,
        sourceType: 'account',
        sourceId: $user->id,
    );

    $second = $action->execute(
        user: $user,
        roleKey: RoleKey::Guest,
        sourceType: 'account',
        sourceId: $user->id,
    );

    expect($second->id)->toBe($first->id)
        ->and(
            RoleAssignment::query()
                ->where('user_id', $user->id)
                ->whereNull('ends_at')
                ->count()
        )->toBe(1)
        ->and(AuditEvent::query()->count())
        ->toBe(1);
});

it('rejects roles that are not assigned automatically', function () {
    $user = User::query()->create([
        'email' => 'manual-role@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    expect(
        fn () => app(AssignAutomaticRoleAction::class)->execute(
            user: $user,
            roleKey: RoleKey::Team,
        )
    )->toThrow(\InvalidArgumentException::class);

    expect(RoleAssignment::query()->count())->toBe(0)
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('rolls the assignment and audit back with an outer transaction', function () {
    $user = User::query()->create([
        'email' => 'rollback-role@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    expect(fn () => DB::transaction(function () use ($user): void {
        app(AssignAutomaticRoleAction::class)->execute(
            user: $user,
            roleKey: RoleKey::Guest,
            sourceType: 'account',
            sourceId: $user->id,
        );

        throw new \RuntimeException('force outer rollback');
    }))->toThrow(
        \RuntimeException::class,
        'force outer rollback',
    );

    expect(RoleAssignment::query()->count())->toBe(0)
        ->and(AuditEvent::query()->count())->toBe(0);
});
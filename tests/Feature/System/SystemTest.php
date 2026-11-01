<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\System\Models\SystemTaskRun;
use App\Modules\System\Services\SystemChecks;
use App\Modules\System\Services\SystemTasks;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function sysUser(?RoleKey $role = null): User
{
    test()->seed(RoleSeeder::class);
    $user = User::factory()->create(['password' => Hash::make('geheim-12345')]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subDay()]);
    }

    return $user;
}

it('lets only the administration into the system pages', function () {
    $this->get(route('system.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember, RoleKey::Coordination, RoleKey::Member] as $role) {
        $this->actingAs(sysUser($role))->get(route('system.index'))->assertForbidden();
        $this->get(route('system.tasks'))->assertForbidden();
        $this->post(route('system.tasks.run', 'cache-leeren'))->assertForbidden();
    }

    $admin = sysUser(RoleKey::Administration);
    $this->actingAs($admin)->get(route('system.index'))->assertOk()->assertSee('Prüfungen')->assertSee('Migrationen');
    $this->get(route('system.tasks'))->assertOk()->assertSee('Cache leeren')->assertSee('Migrationen ausführen');
});

it('lists the system pages in the area Admins', function () {
    $this->actingAs(sysUser(RoleKey::Administration))->get(route('admins.home'))->assertOk()->assertSee('Aufgaben');
});

it('runs checks without failing and reports the scheduler heartbeat', function () {
    $checks = app(SystemChecks::class);

    expect($checks->all())->not->toBeEmpty()
        ->and(collect($checks->all())->pluck('status')->unique()->diff(['ok', 'warning', 'error', 'info'])->all())->toBe([])
        ->and($checks->schedulerAgeMinutes())->toBeNull();

    Cache::forever(SystemChecks::HEARTBEAT_KEY, time() - 120);

    expect($checks->schedulerAgeMinutes())->toBe(2)
        ->and(collect($checks->all())->firstWhere('label', 'Planer (Scheduler)')['status'])->toBe('ok');
});

it('beats the scheduler heartbeat every minute', function () {
    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => $e->description === 'system-heartbeat');

    expect($event)->not->toBeNull()->and($event->expression)->toBe('* * * * *');
});

it('knows only tasks of the fixed list and protects the risky ones with the password', function () {
    $tasks = app(SystemTasks::class);

    expect($tasks->find('migrationen'))->not->toBeNull()
        ->and($tasks->find('migrate:fresh'))->toBeNull()
        ->and($tasks->needsPassword('migrationen'))->toBeTrue()
        ->and($tasks->needsPassword('cache-leeren'))->toBeFalse()
        ->and(collect($tasks->all())->pluck('command')->all())->not->toContain('migrate:fresh', 'db:wipe');

    $this->actingAs(sysUser(RoleKey::Administration))->post(route('system.tasks.run', 'gibt-es-nicht'))->assertNotFound();
});

it('runs a low risk task, keeps the run and logs it without details', function () {
    $admin = sysUser(RoleKey::Administration);

    $this->actingAs($admin)->post(route('system.tasks.run', 'ansichten-leeren'))->assertRedirect(route('system.tasks'))->assertSessionHas('success');

    $run = SystemTaskRun::query()->firstOrFail();
    expect($run->task)->toBe('ansichten-leeren')->and($run->status)->toBe('success')->and($run->user_id)->toBe($admin->id)->and($run->finished_at)->not->toBeNull();

    $entry = AuditEntry::query()->where('event', AuditEvent::SystemTaskRun->value)->firstOrFail();
    expect($entry->context['aufgabe'])->toBe('Ansichten leeren')->and($entry->context['ergebnis'])->toBe('erfolgreich');

    $this->get(route('system.tasks'))->assertOk()->assertSee('Ansichten leeren')->assertSee('Erfolgreich');
});

it('asks for the password before migrations and runs them with the right one', function () {
    $admin = sysUser(RoleKey::Administration);

    $this->actingAs($admin)->post(route('system.tasks.run', 'migrationen'))->assertSessionHasErrors('password');
    $this->post(route('system.tasks.run', 'migrationen'), ['password' => 'falsch'])->assertSessionHasErrors('password');
    expect(SystemTaskRun::query()->count())->toBe(0);

    $this->post(route('system.tasks.run', 'migrationen'), ['password' => 'geheim-12345'])->assertRedirect(route('system.tasks'))->assertSessionHas('success');
    expect(SystemTaskRun::query()->where('task', 'migrationen')->value('status'))->toBe('success');
});

it('offers the maintenance mode only in the matching state and never runs two tasks at once', function () {
    $tasks = app(SystemTasks::class);

    expect($tasks->available('wartung-an'))->toBeTrue()->and($tasks->available('wartung-aus'))->toBeFalse();

    $admin = sysUser(RoleKey::Administration);
    $lock = Cache::lock('system-task', 60);
    $lock->get();

    $this->actingAs($admin)->post(route('system.tasks.run', 'cache-leeren'))->assertRedirect(route('system.tasks'))->assertSessionHas('error');
    expect(SystemTaskRun::query()->count())->toBe(0);

    $lock->release();
});

it('keeps secrets out of the stored output', function () {
    $admin = sysUser(RoleKey::Administration);
    $method = new ReflectionMethod(SystemTasks::class, 'clean');
    $out = $method->invoke(app(SystemTasks::class), 'Schlüssel '.config('app.key').' Ende');

    expect($out)->not->toContain((string) config('app.key'))->and($out)->toContain('***');
});

it('labels the system event in the audit log', function () {
    expect(AuditEvent::SystemTaskRun->label())->toBe('Systemaufgabe ausgeführt')->and(AuditEvent::categories())->toHaveKey(AuditEvent::SystemTaskRun->category());
});

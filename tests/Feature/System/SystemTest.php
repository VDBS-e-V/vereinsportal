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
use App\Modules\System\Services\SystemLogs;
use App\Modules\System\Services\SystemTasks;
use App\Modules\System\Support\Redactor;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'system-heartbeat');

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

it('gives a board member the system pages only after the console grants the administration role', function () {
    $board = sysUser(RoleKey::BoardMember);

    $this->actingAs($board)->get(route('admins.home'))->assertOk()->assertSee('Protokoll')->assertDontSee('/admins/system');
    $this->get(route('system.index'))->assertForbidden();

    $this->artisan('role:grant-administration', ['email' => $board->email])->assertSuccessful();
    $this->artisan('role:grant-administration', ['email' => $board->email])->assertSuccessful();
    $this->artisan('role:grant-administration', ['email' => 'unbekannt@beispiel.example'])->assertFailed();

    expect($board->activeRoleAssignments()->whereHas('role', fn ($q) => $q->where('key', RoleKey::Administration->value))->count())->toBe(1);

    $this->actingAs($board->fresh())->get(route('admins.home'))->assertOk()->assertSee('/admins/system', false);
    $this->get(route('system.index'))->assertOk();
});

it('keeps the new system pages for the administration only and lists them in the submenu', function () {
    $routes = ['system.logs', 'system.queue', 'system.settings'];

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember, RoleKey::Member] as $role) {
        $this->actingAs(sysUser($role));

        foreach ($routes as $route) {
            $this->get(route($route))->assertForbidden();
        }

        $this->post(route('system.settings.test-mail'), ['to' => 'a@beispiel.example'])->assertForbidden();
        $this->delete(route('system.queue.flush'))->assertForbidden();
    }

    $this->actingAs(sysUser(RoleKey::Administration));

    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }

    $this->get(route('admins.home'))->assertSee('Fehlerprotokoll')->assertSee('Warteschlange')->assertSee('Einstellungen');
});

it('reads the log file newest first, filters by level and search and redacts secrets', function () {
    $file = storage_path('logs/zz-test-'.uniqid().'.log');
    file_put_contents($file, implode("\n", [
        '[2026-11-01 10:00:00] testing.INFO: Alter Eintrag',
        '[2026-11-01 10:05:00] testing.ERROR: Mail kaputt password=geheim123 token: abc123def',
        '#0 /app/Beispiel.php(12): wirft()',
        '#1 {main}',
        '[2026-11-01 10:10:00] testing.WARNING: Neuer Hinweis',
    ])."\n");

    try {
        $logs = app(SystemLogs::class);
        $all = $logs->read();

        expect($all['entries'])->toHaveCount(3)
            ->and($all['entries'][0]['message'])->toBe('Neuer Hinweis')
            ->and($all['entries'][1]['detail'])->toContain('Beispiel.php')
            ->and($all['entries'][1]['message'])->not->toContain('geheim123')->not->toContain('abc123def')->toContain('***');

        expect($logs->read('ab-fehler')['entries'])->toHaveCount(1)
            ->and($logs->read('warning')['entries'])->toHaveCount(1)
            ->and($logs->read(null, 'alter')['entries'])->toHaveCount(1)
            ->and($logs->read(null, 'gibtesnicht')['entries'])->toBe([]);

        $this->actingAs(sysUser(RoleKey::Administration))->get(route('system.logs', ['stufe' => 'ab-fehler']))->assertOk()->assertSee('Mail kaputt')->assertDontSee('Neuer Hinweis');
    } finally {
        @unlink($file);
    }
});

it('redacts secrets from texts', function () {
    $clean = Redactor::clean('Anmeldung mit password=Passwort99 und Authorization: Bearer abc.def-ghi fehlgeschlagen');

    expect($clean)->not->toContain('Passwort99')->not->toContain('abc.def-ghi')->toContain('***');
});

it('lists failed jobs and lets the administration retry, forget and flush them', function () {
    if (! Schema::hasTable('failed_jobs')) {
        $this->markTestSkipped('Die Tabelle failed_jobs fehlt.');
    }

    $admin = sysUser(RoleKey::Administration);
    $insert = fn (string $uuid) => DB::table('failed_jobs')->insert(['uuid' => $uuid, 'connection' => 'database', 'queue' => 'default', 'payload' => json_encode(['displayName' => 'App\\Jobs\\Beispiel', 'job' => 'x']), 'exception' => "RuntimeException: kaputt\n#0 stack", 'failed_at' => now()]);
    $insert('aaaaaaaa-0000-0000-0000-000000000001');
    $insert('aaaaaaaa-0000-0000-0000-000000000002');

    $this->actingAs($admin)->get(route('system.queue'))->assertOk()->assertSee('App\Jobs\Beispiel')->assertSee('RuntimeException: kaputt');

    $this->delete(route('system.queue.forget', 'aaaaaaaa-0000-0000-0000-000000000001'))->assertRedirect(route('system.queue'))->assertSessionHas('success');
    expect(DB::table('failed_jobs')->count())->toBe(1);

    $this->delete(route('system.queue.flush'))->assertSessionHasErrors('password');
    expect(DB::table('failed_jobs')->count())->toBe(1);

    $this->delete(route('system.queue.flush'), ['password' => 'geheim-12345'])->assertRedirect(route('system.queue'))->assertSessionHas('success');
    expect(DB::table('failed_jobs')->count())->toBe(0);

    $this->post(route('system.queue.retry', 'gibt-es-nicht'))->assertSessionHas('error');
});

it('shows the settings and sends a test mail with validation and a log entry', function () {
    $admin = sysUser(RoleKey::Administration);

    $this->actingAs($admin)->get(route('system.settings'))->assertOk()->assertSee('Aufbewahrungsfristen')->assertSee('AUDIT_RETENTION_DAYS')->assertSee('Testmail senden');

    $this->post(route('system.settings.test-mail'), ['to' => 'kein-mail'])->assertSessionHasErrors('to');

    config(['mail.default' => 'array']);
    $this->post(route('system.settings.test-mail'), ['to' => 'ziel@beispiel.example'])->assertRedirect(route('system.settings'))->assertSessionHas('success');

    expect(AuditEntry::query()->where('event', AuditEvent::SystemTaskRun->value)->get()->pluck('context.aufgabe')->all())->toContain('Testmail');
});

it('posts every task to its own address and gives every dialog a unique id', function () {
    $html = $this->actingAs(sysUser(RoleKey::Administration))->get(route('system.tasks'))->assertOk()->getContent();

    foreach (['cache-leeren', 'migrationen', 'mitgliedschaften-abgleichen', 'protokoll-aufraeumen', 'wartung-an'] as $key) {
        expect($html)->toContain('/admins/system/aufgaben/'.$key);
    }

    expect($html)->not->toMatch('#/admins/system/aufgaben/\d+#');

    preg_match_all('/<dialog id="([^"]+)"/', $html, $ids);

    expect($ids[1])->not->toBeEmpty()->and(array_unique($ids[1]))->toHaveCount(count($ids[1]))->and($ids[1])->toContain('task-migrationen', 'task-wartung-an');
});

it('does not show the maintenance access link while the portal is up', function () {
    expect(app(SystemTasks::class)->maintenanceSecret())->toBeNull();

    $this->actingAs(sysUser(RoleKey::Administration))->get(route('system.tasks'))->assertOk()->assertDontSee('Der Wartungsmodus ist');
});

it('keeps the routes cacheable: the fallback route is a controller, not a closure', function () {
    $fallback = collect(app('router')->getRoutes()->getRoutes())->first(fn ($route) => $route->isFallback);

    expect($fallback)->not->toBeNull()->and($fallback->getAction('uses'))->not->toBeInstanceOf(Closure::class)->and($fallback->getActionName())->toContain('NotFoundController');
});

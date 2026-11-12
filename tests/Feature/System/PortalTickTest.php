<?php

use App\Modules\System\Models\CronRun;
use App\Modules\System\Services\PortalTick;
use App\Modules\System\Services\SystemChecks;
use App\Modules\System\Services\SystemTasks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    config(['portal.cron.seconds' => 5]);
    Storage::fake('local');
});

function tickKey(): string
{
    return str_repeat('k', 40);
}

it('does not know the cron address without a key, with a short key or with a wrong key', function () {
    config(['portal.cron.key' => null]);
    $this->get('/cron/'.tickKey())->assertNotFound();

    config(['portal.cron.key' => 'zu-kurz-zu-kurz']);
    $this->get('/cron/zu-kurz-zu-kurz')->assertNotFound();

    config(['portal.cron.key' => tickKey()]);
    $this->get('/cron/'.str_repeat('x', 40))->assertNotFound();
    expect(CronRun::query()->count())->toBe(0);
});

it('runs a tick on the right key, answers empty and records the run and the scheduler heartbeat', function () {
    config(['portal.cron.key' => tickKey()]);
    Cache::forget(SystemChecks::HEARTBEAT_KEY);

    $this->get('/cron/'.tickKey())->assertNoContent();

    $run = CronRun::query()->firstOrFail();
    expect($run->status)->toBe('ok')->and($run->source)->toBe('web')->and($run->finished_at)->not->toBeNull()
        ->and(app(SystemChecks::class)->schedulerAgeMinutes())->toBe(0);
});

it('does not start a second tick while one is running', function () {
    $lock = Cache::lock('portal-tick', 30);
    expect($lock->get())->toBeTrue();

    $result = app(PortalTick::class)->run('console');
    $lock->release();

    expect($result['status'])->toBe('busy')->and(CronRun::query()->count())->toBe(0);
});

it('works through waiting jobs of the database queue', function () {
    config(['queue.default' => 'database']);
    Cache::forget('tick-test-job');

    dispatch(function (): void {
        Cache::put('tick-test-job', 'done', 60);
    });

    $result = app(PortalTick::class)->run('console');

    expect($result['status'])->toBe('ok')->and($result['jobs'])->toBe(1)->and(Cache::get('tick-test-job'))->toBe('done')
        ->and(CronRun::query()->firstOrFail()->jobs_run)->toBe(1);
});

it('runs from the console command', function () {
    $this->artisan('portal:tick')->assertSuccessful();

    expect(CronRun::query()->firstOrFail()->source)->toBe('console');
});

it('shows the cron state in the system checks', function () {
    config(['portal.cron.key' => null]);
    expect(json_encode(app(SystemChecks::class)->all(), JSON_UNESCAPED_UNICODE))->toContain('Nicht eingerichtet');

    config(['portal.cron.key' => tickKey()]);
    expect(json_encode(app(SystemChecks::class)->all(), JSON_UNESCAPED_UNICODE))->toContain('noch nie aufgerufen');

    app(PortalTick::class)->run('console');
    expect(json_encode(app(SystemChecks::class)->all(), JSON_UNESCAPED_UNICODE))->toContain('Letzter Aufruf vor');
});

it('forgets cron runs after fourteen days', function () {
    CronRun::query()->create(['source' => 'web', 'started_at' => now()->subDays(20), 'status' => 'ok']);

    app(PortalTick::class)->run('console');

    expect(CronRun::query()->where('started_at', '<', now()->subDays(14))->count())->toBe(0);
});

it('portal:update migriert, leert den Cache und beendet die Wartung wieder', function () {
    $this->artisan('portal:update', ['--skip-verify' => true])->assertSuccessful();

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('portal:update bricht bei unvollständigem Paket ab, ohne die Wartung einzuschalten', function () {
    $path = base_path('release-manifest.json');
    $existed = is_file($path);
    $backup = $existed ? file_get_contents($path) : null;
    file_put_contents($path, json_encode(['version' => 'test', 'files' => ['gibt-es-nicht.php' => ['size' => 1, 'sha256' => 'x']]]));

    try {
        $this->artisan('portal:update')->assertFailed();
        expect(app()->isDownForMaintenance())->toBeFalse();
    } finally {
        $existed ? file_put_contents($path, $backup) : unlink($path);
    }
});

it('bietet das Update als Aufgabe der Systemsteuerung mit Passwort an', function () {
    $tasks = app(SystemTasks::class);

    expect($tasks->find('update')['command'])->toBe('portal:update')
        ->and($tasks->needsPassword('update'))->toBeTrue();
});

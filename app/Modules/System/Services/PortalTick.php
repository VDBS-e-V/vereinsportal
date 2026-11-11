<?php

namespace App\Modules\System\Services;

use App\Modules\System\Models\CronRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Ein Takt des Portals für Webhosting ohne Dauerprozesse: Planer ausführen (was gerade fällig ist), danach Aufträge der Warteschlange bis zu einer
 * Zeitgrenze abarbeiten. Aufgerufen von `php artisan portal:tick` oder von der Adresse /cron/{key}. Eine Sperre verhindert parallele Läufe.
 */
final class PortalTick
{
    /** @return array{status: string, jobs: int, ms: int} status: ok, error oder busy */
    public function run(string $source = 'web'): array
    {
        $lock = Cache::lock('portal-tick', max(30, $this->seconds() + 20));

        if (! $lock->get()) {
            return ['status' => 'busy', 'jobs' => 0, 'ms' => 0];
        }

        $started = microtime(true);
        $run = null;

        try {
            $run = Schema::hasTable('cron_runs') ? CronRun::query()->create(['source' => $source, 'started_at' => now(), 'status' => 'running']) : null;
            $before = $this->waiting();

            Artisan::call('schedule:run');

            $this->work();

            $jobs = max(0, $before - $this->waiting());
            $ms = (int) round((microtime(true) - $started) * 1000);
            $run?->forceFill(['finished_at' => now(), 'duration_ms' => $ms, 'jobs_run' => $jobs, 'status' => 'ok'])->save();
            $this->prune();

            return ['status' => 'ok', 'jobs' => $jobs, 'ms' => $ms];
        } catch (Throwable $exception) {
            report($exception);
            $ms = (int) round((microtime(true) - $started) * 1000);
            $run?->forceFill(['finished_at' => now(), 'duration_ms' => $ms, 'status' => 'error', 'error' => mb_substr(class_basename($exception).': '.$exception->getMessage(), 0, 200)])->save();

            return ['status' => 'error', 'jobs' => 0, 'ms' => $ms];
        } finally {
            $lock->release();
        }
    }

    public function seconds(): int
    {
        return max(5, min(200, (int) config('portal.cron.seconds', 45)));
    }

    /** Arbeitet wartende Aufträge ab, höchstens `seconds()` Sekunden lang. */
    private function work(): void
    {
        Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => $this->seconds(), '--tries' => 3, '--sleep' => 1, '--quiet' => true]);
    }

    private function waiting(): int
    {
        try {
            return config('queue.default') === 'database' && Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private function prune(): void
    {
        CronRun::query()->where('started_at', '<', now()->subDays(14))->delete();
    }
}

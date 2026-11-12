<?php

namespace App\Modules\System\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul: System. Ein Lauf des Cron-Aufrufs (portal:tick): wann, wie lange, wie viele Aufträge. Aufbewahrung 14 Tage. */
class CronRun extends Model
{
    public $timestamps = false;

    protected $fillable = ['source', 'started_at', 'finished_at', 'duration_ms', 'jobs_run', 'status', 'error'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}

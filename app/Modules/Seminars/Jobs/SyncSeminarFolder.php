<?php

namespace App\Modules\Seminars\Jobs;

use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarNextcloud;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Legt den Nextcloud-Ordner eines Seminars im Hintergrund an (wiederholbar, ohne Folgen bei Mehrfachaufruf). */
final class SyncSeminarFolder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly int $seminarId) {}

    public function handle(SeminarNextcloud $nextcloud): void
    {
        $seminar = Seminar::query()->find($this->seminarId);

        if ($seminar !== null) {
            $nextcloud->ensureFolders($seminar);
        }
    }
}

<?php

namespace App\Modules\Seminars\Jobs;

use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Nextcloud\SeminarNextcloud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Legt die Nextcloud-Ordner eines Seminars an und gleicht den Zugriff ab (wiederholbar, ohne Doppelungen). */
class SyncSeminarFolder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Seminar $seminar) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(SeminarNextcloud $nextcloud): void
    {
        if ($nextcloud->enabled()) {
            $nextcloud->provision($this->seminar->fresh());
        }
    }
}

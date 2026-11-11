<?php

namespace App\Modules\Mailings\Console;

use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Services\Mailings;
use DomainException;
use Illuminate\Console\Command;

/** Startet geplante Schreiben, deren Zeitpunkt erreicht ist (jede Minute im Planer). Ohne Empfänger zum Sendezeitpunkt gilt das Schreiben als fehlgeschlagen. */
class DispatchDueMailings extends Command
{
    protected $signature = 'mailings:dispatch-due';

    protected $description = 'Startet geplante Schreiben an Gruppen, deren Zeitpunkt erreicht ist.';

    public function handle(Mailings $mailings): int
    {
        $due = Mailing::query()->where('status', Mailing::STATUS_SCHEDULED)->where('send_at', '<=', now())->orderBy('send_at')->get();

        foreach ($due as $mailing) {
            try {
                $mailings->launch($mailing);
            } catch (DomainException $exception) {
                $mailing->forceFill(['status' => Mailing::STATUS_FAILED, 'finished_at' => now(), 'rejection_note' => $exception->getMessage()])->save();
            }
        }

        $this->components->info($due->count().' geplante Schreiben gestartet.');

        return self::SUCCESS;
    }
}

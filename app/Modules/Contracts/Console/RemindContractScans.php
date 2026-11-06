<?php

namespace App\Modules\Contracts\Console;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Contracts\Mail\ContractScanReminderMail;
use App\Modules\Contracts\Models\SeminarContract;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Erinnert die Koordination einmal je Seminar, wenn drei Tage nach dem letzten Termin noch unterschriebene Scans fehlen. */
class RemindContractScans extends Command
{
    protected $signature = 'contracts:remind {--days=3 : Tage nach dem letzten Termin}';

    protected $description = 'Erinnert die Koordination an fehlende unterschriebene Verträge vergangener Seminare (einmal je Vertrag).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $open = SeminarContract::query()->where('status', 'created')->whereNull('reminded_at')->get()
            ->filter(function (SeminarContract $contract) use ($days): bool {
                $end = $contract->seminar?->sessions()->max('ends_at');

                return $end !== null && \Carbon\Carbon::parse($end)->addDays($days)->isPast();
            })->groupBy('seminar_id');

        if ($open->isEmpty()) {
            $this->components->info('Keine Erinnerungen nötig.');

            return self::SUCCESS;
        }

        $recipients = User::query()->whereIn('id', RoleAssignment::query()
            ->where('starts_at', '<=', now())->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->whereHas('role', fn ($query) => $query->where('key', RoleKey::Coordination->value))->select('user_id'))->get()
            ->filter(fn (User $user): bool => $user->status === \App\Modules\Access\Enums\UserStatus::Active);

        foreach ($open as $seminarId => $contracts) {
            $seminar = Seminar::query()->find($seminarId);

            if ($seminar === null) {
                continue;
            }

            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->send(new ContractScanReminderMail($seminar, $contracts->count(), route('seminars.show', $seminar)));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            SeminarContract::query()->whereIn('id', $contracts->pluck('id'))->update(['reminded_at' => now()]);
        }

        $this->components->info('Erinnert zu '.$open->count().' Seminar(en).');

        return self::SUCCESS;
    }
}

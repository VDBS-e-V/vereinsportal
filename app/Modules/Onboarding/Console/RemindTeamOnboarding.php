<?php

namespace App\Modules\Onboarding\Console;

use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\Onboarding\Mail\TeamOnboardingReminderMail;
use App\Modules\Onboarding\Models\TeamOnboarding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RemindTeamOnboarding extends Command
{
    protected $signature = 'onboarding:remind-team {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Erinnert Teamende per E-Mail an eine offene Einarbeitung (nach der konfigurierten Frist, höchstens mehrmals).';

    public function handle(): int
    {
        $after = (int) config('portal.onboarding.reminder.after_days', 14);
        $repeat = (int) config('portal.onboarding.reminder.repeat_days', 14);
        $max = (int) config('portal.onboarding.reminder.max', 3);
        $dry = (bool) $this->option('dry-run');
        $sent = 0;

        TeamOnboarding::query()
            ->whereNull('completed_at')
            ->where('started_at', '<=', now()->subDays($after))
            ->where('reminders_sent', '<', $max)
            ->where(fn ($query) => $query->whereNull('last_reminded_at')->orWhere('last_reminded_at', '<=', now()->subDays($repeat)))
            ->with(['user', 'steps'])->get()
            ->each(function (TeamOnboarding $onboarding) use ($dry, &$sent): void {
                $user = $onboarding->user;

                if ($user === null || ! in_array(RoleKey::Team->value, Capabilities::activeRoleKeys($user), true)) {
                    return;
                }

                [$done, $all] = $onboarding->progress();
                $sent++;

                if ($dry) {
                    return;
                }

                try {
                    Mail::to($user->email)->send(new TeamOnboardingReminderMail(route('team-self.index'), $all - $done, $all));
                    $onboarding->forceFill(['last_reminded_at' => now(), 'reminders_sent' => $onboarding->reminders_sent + 1])->save();
                } catch (Throwable $exception) {
                    report($exception);
                    $sent--;
                }
            });

        $this->info(($dry ? '[Trockenlauf] ' : '')."Erinnerungen: {$sent}.");

        return self::SUCCESS;
    }
}

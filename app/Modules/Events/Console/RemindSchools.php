<?php

namespace App\Modules\Events\Console;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\Events\Enums\EventStatus;
use App\Modules\Events\Mail\SchoolEventReminderMail;
use App\Modules\Events\Models\Event;
use App\Modules\Partners\Models\PartnerUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RemindSchools extends Command
{
    protected $signature = 'events:remind-schools {--dry-run : Nur zählen, nichts senden}';

    protected $description = 'Erinnert aktive Schulkonten einmalig kurz vor dem Anmeldeschluss einer Großveranstaltung.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $days = max(1, (int) config('portal.events.school_reminder_days', 3));
        $count = 0;

        Event::query()->where('status', EventStatus::Open->value)->whereNotNull('registration_deadline')
            ->where('registration_deadline', '>', now())->where('registration_deadline', '<=', now()->addDays($days))->get()
            ->each(function (Event $event) use ($dry, &$count): void {
                $accounts = PartnerUser::query()->whereHas('user', fn ($query) => $query->where('status', UserStatus::Active->value))
                    ->whereNotIn('id', DB::table('school_event_reminders')->where('event_id', $event->id)->select('partner_user_id'))->with('user')->get();

                foreach ($accounts as $account) {
                    if (! app(NotificationPreferences::class)->allows($account->user, 'school_event_reminders')) {
                        continue;
                    }

                    $count++;

                    if ($dry) {
                        continue;
                    }

                    try {
                        Mail::to($account->user->email)->send(new SchoolEventReminderMail($event, $account->name, $event->registrations()->where('partner_id', $account->partner_id)->count(), route('school.events.show', $event)));
                    } catch (Throwable $exception) {
                        report($exception);
                        $count--;

                        continue;
                    }

                    DB::table('school_event_reminders')->insert(['event_id' => $event->id, 'partner_user_id' => $account->id, 'sent_at' => now()]);
                }
            });

        $this->info(($dry ? '[Trockenlauf] ' : '').'Erinnerungen an Schulen: '.$count.'.');

        return self::SUCCESS;
    }
}

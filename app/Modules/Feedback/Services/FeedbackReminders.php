<?php

namespace App\Modules\Feedback\Services;

use App\Modules\Feedback\Mail\FeedbackReminderMail;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Seminars\Enums\ApplicationStatus;
use Illuminate\Support\Facades\Mail;

/**
 * Erinnert Lehrkräfte und Teamende einmal an die Rückmeldung, wenn der letzte Termin eine Woche zurückliegt und noch zu wenig geantwortet wurde
 * (Lehrkräfte: noch keine Antwort; Teamende: weniger als die Hälfte des bestätigten Teams).
 */
final class FeedbackReminders
{
    public function __construct(private readonly FeedbackLinks $links) {}

    /** @return array{links: int, mails: int} Zahlen der Erinnerungen */
    public function run(bool $dryRun = false): array
    {
        $after = max(1, (int) config('portal.feedback.remind_after_days', 7));
        $linkCount = 0;
        $mailCount = 0;

        $candidates = FeedbackLink::query()->whereIn('audience', ['teachers', 'team'])->whereNull('reminded_at')->whereNull('anonymized_at')->whereNull('closed_at')->with(['seminar.sessions', 'seminar.partner.contacts'])->get();

        foreach ($candidates as $link) {
            $seminar = $link->seminar;
            $lastEnd = $seminar?->sessions->max('ends_at');

            if ($seminar === null || $lastEnd === null || now()->lessThan(\Illuminate\Support\Carbon::parse($lastEnd)->addDays($after)) || $this->links->state($link) !== 'open') {
                continue;
            }

            $recipients = $this->recipients($link);
            $needed = $link->audience === 'team' ? (int) ceil(count($recipients) / 2) : 1;

            if ($recipients === [] || $link->responses()->count() >= $needed) {
                continue;
            }

            $linkCount++;

            if ($dryRun) {
                $mailCount += count($recipients);

                continue;
            }

            foreach ($recipients as $email) {
                Mail::to($email)->send(new FeedbackReminderMail($seminar, $link->audience, $link->url(), $link->closes_at->format('d.m.Y')));
                $mailCount++;
            }

            $link->forceFill(['reminded_at' => now()])->save();
        }

        return ['links' => $linkCount, 'mails' => $mailCount];
    }

    /** @return list<string> */
    private function recipients(FeedbackLink $link): array
    {
        $seminar = $link->seminar;

        if ($link->audience === 'team') {
            $emails = $seminar->applications()->where('status', ApplicationStatus::Confirmed->value)->with(['user', 'contact'])->get()->map(fn ($application): ?string => $application->applicantEmail());
        } else {
            $contacts = $seminar->partner?->contacts ?? collect();
            $primary = $contacts->where('is_primary', true);
            $emails = ($primary->isNotEmpty() ? $primary : $contacts)->pluck('email');
        }

        return $emails->filter(fn ($email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))->map(fn (string $email): string => mb_strtolower($email))->unique()->values()->all();
    }
}

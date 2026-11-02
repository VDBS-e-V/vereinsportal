<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Mailings\Models\MailSuppression;

/** Gesperrte Adressen. Eine aktive Sperre je Adresse. Das allgemeine Protokoll nennt nur Aktion und Grund, nie die Adresse. */
final class MailSuppressions
{
    public function isSuppressed(string $email): bool
    {
        return MailSuppression::query()->active()->where('email', mb_strtolower(trim($email)))->exists();
    }

    /**
     * Welche dieser Adressen sind gesperrt?
     *
     * @param  list<string>  $emails
     * @return array<string, true>
     */
    public function among(array $emails): array
    {
        $found = [];

        foreach (array_chunk(array_values(array_unique(array_map(fn (string $e): string => mb_strtolower(trim($e)), $emails))), 500) as $chunk) {
            foreach (MailSuppression::query()->active()->whereIn('email', $chunk)->pluck('email') as $email) {
                $found[(string) $email] = true;
            }
        }

        return $found;
    }

    public function suppress(string $email, string $reason, ?string $note, ?User $by, ?string $source = null): MailSuppression
    {
        $email = mb_strtolower(trim($email));
        $existing = MailSuppression::query()->active()->where('email', $email)->first();

        if ($existing !== null) {
            return $existing;
        }

        $suppression = MailSuppression::query()->create(['email' => $email, 'reason' => $reason, 'source' => $source, 'note' => filled($note) ? trim((string) $note) : null, 'created_by_user_id' => $by?->id]);

        AuditLog::record(AuditEvent::MailSuppressionChanged, $by, null, ['aktion' => 'gesperrt', 'grund' => $suppression->reasonLabel()]);

        return $suppression;
    }

    public function release(MailSuppression $suppression, ?User $by): void
    {
        if ($suppression->released_at !== null) {
            return;
        }

        $suppression->forceFill(['released_at' => now(), 'released_by_user_id' => $by?->id])->save();

        AuditLog::record(AuditEvent::MailSuppressionChanged, $by, null, ['aktion' => 'freigegeben', 'grund' => $suppression->reasonLabel()]);
    }
}

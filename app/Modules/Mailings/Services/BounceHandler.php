<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Mailings\Models\MailSoftBounce;
use App\Modules\Mailings\Models\MailSuppression;

/** Verarbeitet Rückmeldungen: dauerhafte Fehler und Beschwerden sperren sofort, vorläufige Fehler zählen und sperren ab der Grenze (portal.mailings.soft_bounce_limit). */
final class BounceHandler
{
    public function __construct(private readonly MailSuppressions $suppressions) {}

    /**
     * @param  list<array{type: string, email: string}>  $events
     * @return array{blocked: int, counted: int}
     */
    public function handle(array $events): array
    {
        $blocked = 0;
        $counted = 0;

        foreach ($events as $event) {
            if ($event['type'] === 'soft_bounce') {
                $counted++;
                $blocked += $this->soft($event['email']) ? 1 : 0;

                continue;
            }

            $reason = $event['type'] === 'complaint' ? MailSuppression::COMPLAINT : MailSuppression::HARD_BOUNCE;

            if (! $this->suppressions->isSuppressed($event['email'])) {
                $this->suppressions->suppress($event['email'], $reason, null, null, 'webhook');
                $blocked++;
            }
        }

        return ['blocked' => $blocked, 'counted' => $counted];
    }

    /** Zählt einen vorläufigen Fehler. Zählungen, die über 30 Tage zurückliegen, beginnen von vorn. Gibt zurück, ob jetzt gesperrt wurde. */
    private function soft(string $email): bool
    {
        $limit = max(1, (int) config('portal.mailings.soft_bounce_limit', 5));
        $row = MailSoftBounce::query()->firstOrNew(['email' => $email]);
        $stale = $row->last_at !== null && $row->last_at->lt(now()->subDays(30));
        $row->count = ($stale ? 0 : (int) $row->count) + 1;
        $row->last_at = now();
        $row->save();

        if ($row->count < $limit || $this->suppressions->isSuppressed($email)) {
            return false;
        }

        $this->suppressions->suppress($email, MailSuppression::HARD_BOUNCE, 'Wiederholt vorläufige Zustellfehler', null, 'webhook');
        $row->delete();

        return true;
    }
}

<?php

namespace App\Modules\Onboarding\Listeners;

use App\Modules\Membership\Events\MembershipAdmitted;
use App\Modules\Onboarding\Services\MemberWelcome;
use Throwable;

/** Beim Aufnehmen eines Antrags geht das Willkommenspaket an die Person. Fehler beim Senden verhindern die Aufnahme nicht. */
final class SendWelcomeOnAdmission
{
    public function __construct(private readonly MemberWelcome $welcome) {}

    public function handle(MembershipAdmitted $event): void
    {
        if (! config('portal.onboarding.welcome_on_admission')) {
            return;
        }

        try {
            $this->welcome->send($event->membership, null);
        } catch (Throwable $exception) {
            if (! $exception instanceof \DomainException) {
                report($exception);
            }
        }
    }
}

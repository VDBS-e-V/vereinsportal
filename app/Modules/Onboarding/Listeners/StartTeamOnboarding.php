<?php

namespace App\Modules\Onboarding\Listeners;

use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Events\RoleGranted;
use App\Modules\Onboarding\Services\TeamOnboardings;

/** Mit der Rolle Teamende beginnt die Einarbeitung. */
final class StartTeamOnboarding
{
    public function __construct(private readonly TeamOnboardings $onboardings) {}

    public function handle(RoleGranted $event): void
    {
        if ($event->role->key === RoleKey::Team->value) {
            $this->onboardings->ensure($event->user, $event->by);
        }
    }
}

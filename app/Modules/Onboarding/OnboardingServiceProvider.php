<?php

namespace App\Modules\Onboarding;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\AccessGovernance\Events\RoleGranted;
use App\Modules\Membership\Events\MembershipAdmitted;
use App\Modules\Onboarding\Console\RemindTeamOnboarding;
use App\Modules\Onboarding\Listeners\RemoveOnboardingOnAccountDeletion;
use App\Modules\Onboarding\Listeners\SendWelcomeOnAdmission;
use App\Modules\Onboarding\Listeners\StartTeamOnboarding;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Onboarding in zwei getrennten Abläufen: Mitglieder (Willkommenspaket nach der Aufnahme, Bereich Vorstand) und Teamende (Einarbeitung nach der
 * Rollenvergabe, Bereiche Koordination und Teamende).
 */
class OnboardingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([RemindTeamOnboarding::class]);
        }

        Event::listen(MembershipAdmitted::class, SendWelcomeOnAdmission::class);
        Event::listen(RoleGranted::class, StartTeamOnboarding::class);
        Event::listen(AccountAnonymizing::class, RemoveOnboardingOnAccountDeletion::class);
    }
}

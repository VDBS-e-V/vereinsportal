<?php

namespace App\Modules\Onboarding\Listeners;

use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\Onboarding\Models\TeamOnboarding;

/** Beim Löschen eines Kontos entfällt die Einarbeitung mit allen Schritten (Daten, Notizen). */
final class RemoveOnboardingOnAccountDeletion
{
    public function handle(AccountAnonymizing $event): void
    {
        TeamOnboarding::query()->where('user_id', $event->user->id)->delete();
    }
}

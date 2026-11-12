<?php

namespace App\Modules\Membership\Events;

use App\Modules\Access\Models\User;
use App\Modules\Membership\Models\Membership;

/** Der Vorstand hat einen Antrag aufgenommen (nicht bei vom Vorstand erfassten Altbeständen). Das Onboarding sendet darauf das Willkommenspaket. */
final class MembershipAdmitted
{
    public function __construct(public readonly Membership $membership, public readonly User $by) {}
}

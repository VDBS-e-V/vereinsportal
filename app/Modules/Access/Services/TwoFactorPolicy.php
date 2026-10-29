<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\User;

/**
 * Pflicht zur Zwei-Faktor-Anmeldung: gilt für alle, die zurzeit eine der Rollen aus config('portal.two_factor.roles') haben
 * (Verwaltungsrechte: Administration, Geschäftsstelle, Vorstand), wenn config('portal.two_factor.required') eingeschaltet ist
 * (TWO_FACTOR_REQUIRED, Standard an). Alle anderen Konten bleiben bei freiwilliger Nutzung.
 */
final class TwoFactorPolicy
{
    public function __construct(private readonly TwoFactorManager $twoFactor) {}

    public function isRequired(User $user): bool
    {
        if (! config('portal.two_factor.required')) {
            return false;
        }

        $roles = (array) config('portal.two_factor.roles', []);

        return $roles !== [] && $user->activeRoleAssignments()->whereHas('role', fn ($role) => $role->whereIn('key', $roles))->exists();
    }

    /** Pflicht besteht und es ist noch keine Methode eingerichtet. */
    public function mustSetUp(User $user): bool
    {
        return $this->isRequired($user) && ! $this->twoFactor->isEnabled($user);
    }
}

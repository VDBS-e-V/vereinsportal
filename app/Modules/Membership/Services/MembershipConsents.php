<?php

namespace App\Modules\Membership\Services;

use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipConsent;

/**
 * Hält fest, welcher Fassung von Satzung und Datenschutzhinweisen jemand zugestimmt hat. Die aktuellen Fassungen stehen in
 * config('portal.legal'); bei einer neuen Fassung ändert man dort die Kennung (z. B. 2027-01).
 */
final class MembershipConsents
{
    /** @param  list<string>  $types  statute | privacy */
    public function record(Membership $membership, array $types): void
    {
        foreach ($types as $type) {
            if (! array_key_exists($type, MembershipConsent::TYPES)) {
                continue;
            }

            $membership->consents()->create([
                'type' => $type,
                'version' => (string) config("portal.legal.{$type}_version", 'unbekannt'),
                'accepted_at' => now(),
            ]);
        }
    }
}

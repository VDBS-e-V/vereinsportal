<?php

namespace App\Modules\Access\Events;

use App\Modules\Access\Models\User;
use App\Modules\People\Models\Person;

/**
 * Wird ausgelöst, bevor ein Konto anonymisiert wird (innerhalb der Transaktion). Andere Module räumen hier ihre
 * personenbezogenen Daten auf (z. B. Mitgliedschaften), solange die Person noch lesbar ist.
 */
final class AccountAnonymizing
{
    public function __construct(public readonly User $user, public readonly ?Person $person) {}
}

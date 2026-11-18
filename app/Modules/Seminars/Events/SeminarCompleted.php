<?php

namespace App\Modules\Seminars\Events;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Foundation\Events\Dispatchable;

/** Ein Seminar ist abgeschlossen. Hier dockt später die Abrechnung an; die Kostenstelle bleibt bewusst außen vor. */
final class SeminarCompleted
{
    use Dispatchable;

    public function __construct(public readonly Seminar $seminar) {}
}

<?php

namespace App\Modules\Seminars\Events;

use App\Modules\Seminars\Models\Seminar;

/** Ein Seminar ist abgeschlossen. Daran dockt später die Abrechnung (Kostenstelle) an; das Seminar selbst kennt sie nicht. */
final class SeminarCompleted
{
    public function __construct(public readonly Seminar $seminar) {}
}

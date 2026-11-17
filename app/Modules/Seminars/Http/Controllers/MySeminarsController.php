<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Koordination: „Meine Seminare“ – freigegebene Seminare bis zum Abschluss mit dem nächsten Schritt je Status. */
class MySeminarsController extends Controller
{
    /** Nächster Schritt der Koordination je Status. */
    public const NEXT = [
        'released' => 'Termine und Ort eintragen, Teamgröße festlegen und das Seminar planen.',
        'planned' => 'Stichtag für Interesse setzen und das Seminar ausschreiben.',
        'tendered' => 'Interessierte sichten und nach dem Stichtag Zusagen und Absagen markieren.',
        'team_selected' => 'Seminarleitung bestimmen und das Team bestätigen.',
        'team_confirmed' => 'Unterlagen bereitstellen, Onboarding planen und das Seminar als vorbereitet markieren.',
        'prepared' => 'Durchführung abwarten. Bei Ausfall: Team nachrücken lassen oder absagen.',
        'held' => 'Verträge und Ergebnisbericht prüfen und das Seminar abschließen.',
        'reported' => 'Bericht prüfen und das Seminar abschließen.',
    ];

    public function index(Request $request): View
    {
        $all = $request->boolean('all');
        $user = $request->user();

        $seminars = Seminar::query()
            ->with(['partner', 'sessions'])
            ->whereIn('status', SeminarStatus::values([...SeminarStatus::planning(), SeminarStatus::Held, SeminarStatus::Reported]))
            ->when(! $all, fn ($query) => $query->where('coordinator_user_id', $user?->id))
            ->orderBy('status')->orderByDesc('updated_at')
            ->get();

        return view('seminars.mine', ['seminars' => $seminars, 'all' => $all, 'next' => self::NEXT]);
    }
}

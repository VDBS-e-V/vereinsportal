<?php

namespace App\Modules\Board\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Board\Services\BoardCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Der gemeinsame Kalenderlink des Vorstands. Sehen und ändern darf ihn nur der Vorstand. */
class BoardCalendarController extends Controller
{
    public function __construct(private readonly BoardCalendar $calendar) {}

    public function show(): View
    {
        $settings = $this->calendar->all();

        return view('board.calendar', ['settings' => $settings, 'webcal' => $settings['feed_url'] !== '' ? BoardCalendar::webcal($settings['feed_url']) : null]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'calendar_url' => ['nullable', 'string', 'max:500', 'url:https'],
            'feed_url' => ['nullable', 'string', 'max:500', 'url:https'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'calendar_url.url' => 'Bitte geben Sie eine vollständige Adresse an, die mit https:// beginnt.',
            'feed_url.url' => 'Bitte geben Sie eine vollständige Adresse an, die mit https:// beginnt.',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $this->calendar->save($data, $user);

        return redirect()->route('board.calendar.show')->with('success', 'Der Kalender des Vorstands wurde gespeichert.');
    }
}

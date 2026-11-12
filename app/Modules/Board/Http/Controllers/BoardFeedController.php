<?php

namespace App\Modules\Board\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Services\BoardCalendar;
use App\Modules\Board\Support\IcsFeed;
use Illuminate\Http\Response;

/** Der iCal-Feed der Vorstandssitzungen. Ohne gültigen Schlüssel antwortet er mit 404, als gäbe es ihn nicht. */
class BoardFeedController extends Controller
{
    public function __invoke(BoardCalendar $calendar, string $token): Response
    {
        abort_unless($calendar->validToken($token), 404);

        $meetings = BoardMeeting::query()->where('starts_at', '>=', now()->subDays(90))->orderBy('starts_at')->get();

        return response(IcsFeed::build($meetings, parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'portal'), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="vorstandssitzungen.ics"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}

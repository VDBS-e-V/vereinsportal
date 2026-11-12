<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\SystemLogs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/** Fehlerprotokoll (Bereich Admins): das Ende der Anwendungs-Protokolldatei mit Filter nach Stufe und Suchtext, nur lesen. */
class SystemLogController extends Controller
{
    private const PER_PAGE = 50;

    public function __construct(private readonly SystemLogs $logs) {}

    public function index(Request $request): View
    {
        $level = (string) $request->query('stufe', '');
        $level = $level === 'ab-fehler' || in_array($level, SystemLogs::LEVELS, true) ? $level : null;
        $search = trim((string) $request->query('q', ''));

        $result = $this->logs->read($level, $search);
        $page = max(1, (int) $request->query('page', 1));
        $slice = array_slice($result['entries'], ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        $paginator = new LengthAwarePaginator($slice, count($result['entries']), self::PER_PAGE, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('system.logs', [
            'entries' => $paginator,
            'file' => $result['file'],
            'size' => $result['size'],
            'truncated' => $result['truncated'],
            'filters' => ['q' => $search !== '' ? $search : null, 'level' => $level],
            'active' => collect([
                $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
                $level !== null ? ['label' => 'Stufe: '.($level === 'ab-fehler' ? 'ab Fehler' : $level), 'href' => $request->fullUrlWithoutQuery(['stufe', 'page'])] : null,
            ])->filter()->values()->all(),
            'levelOptions' => ['ab-fehler' => 'Ab Fehler (error und schlimmer)'] + array_combine(SystemLogs::LEVELS, SystemLogs::LEVELS),
        ]);
    }
}

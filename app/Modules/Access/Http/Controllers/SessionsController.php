<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Http\Middleware\EnsureSessionVersion;
use App\Modules\Access\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Konto > Sitzungen: wo das Konto gerade angemeldet ist, und „alle anderen abmelden“.
 * Die Liste kommt aus der Sitzungstabelle (Treiber database); bei anderen Treibern erscheint nur die aktuelle Sitzung.
 * Das Beenden funktioniert immer über die Sitzungsversion des Kontos (siehe EnsureSessionVersion).
 */
class SessionsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->user($request);
        $currentId = $request->session()->getId();
        $fromDatabase = config('session.driver') === 'database';

        $sessions = $fromDatabase
            ? DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get()
                ->map(fn (object $row): array => [
                    'device' => self::describe((string) $row->user_agent),
                    'ip' => (string) $row->ip_address,
                    'last_activity' => Carbon::createFromTimestamp((int) $row->last_activity),
                    'current' => $row->id === $currentId,
                ])->all()
            : [[
                'device' => self::describe((string) $request->userAgent()),
                'ip' => (string) $request->ip(),
                'last_activity' => now(),
                'current' => true,
            ]];

        return view('account.sessions', ['sessions' => $sessions, 'listAll' => $fromDatabase]);
    }

    /** Meldet alle anderen Sitzungen ab (Passwort erforderlich). */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'Bitte geben Sie Ihr Passwort ein.',
            'password.current_password' => 'Das Passwort ist nicht korrekt.',
        ]);

        $user = $this->user($request);

        $user->forceFill(['session_version' => $user->session_version + 1])->save();
        $request->session()->put(EnsureSessionVersion::KEY, (int) $user->session_version);

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return redirect()->route('account.sessions')->with('success', 'Alle anderen Sitzungen wurden abgemeldet.');
    }

    /** Kurzbeschreibung aus dem User-Agent, z. B. „Firefox auf Windows“. */
    public static function describe(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Unbekannter Browser',
        };

        $system = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS X') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $system !== null ? $browser.' auf '.$system : $browser;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

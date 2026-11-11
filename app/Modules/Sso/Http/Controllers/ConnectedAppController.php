<?php

namespace App\Modules\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sso\Models\OAuthClientSetting;
use App\Modules\Sso\Services\OAuthClients;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Konto › Verbundene Anwendungen: Systeme, bei denen das Konto über das Portal angemeldet wurde, mit Widerruf. */
class ConnectedAppController extends Controller
{
    public function __construct(private readonly OAuthClients $clients) {}

    public function index(Request $request): View
    {
        $rows = DB::table('oauth_access_tokens')->where('user_id', $request->user()->getKey())->where('revoked', false)->where('expires_at', '>', now())->orderByDesc('created_at')->get(['client_id', 'created_at']);
        $apps = [];

        foreach ($rows->groupBy('client_id') as $clientId => $group) {
            $client = $this->clients->find((string) $clientId);

            if ($client === null || $client->revoked) {
                continue;
            }

            $apps[] = ['id' => (string) $clientId, 'name' => (string) $client->name, 'description' => OAuthClientSetting::query()->where('client_id', (string) $clientId)->value('description'), 'since' => \Illuminate\Support\Carbon::parse($group->min('created_at'))];
        }

        return view('sso.connected', ['apps' => $apps]);
    }

    public function destroy(Request $request, string $client): RedirectResponse
    {
        $ids = DB::table('oauth_access_tokens')->where('user_id', $request->user()->getKey())->where('client_id', $client)->pluck('id')->all();

        if ($ids !== []) {
            DB::table('oauth_access_tokens')->whereIn('id', $ids)->update(['revoked' => true]);
            DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $ids)->update(['revoked' => true]);
        }

        return redirect()->route('account.apps')->with('status', 'Die Freigabe wurde widerrufen. Das System muss Sie beim nächsten Mal neu anmelden.');
    }
}

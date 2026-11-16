<?php

namespace App\Modules\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sso\Services\OAuthClients;
use App\Modules\Sso\Services\OAuthConsents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Konto › Verbundene Anwendungen: Systeme, an die das Portal Daten weitergibt (Zustimmung), mit Widerruf. */
class ConnectedAppController extends Controller
{
    public function __construct(private readonly OAuthClients $clients, private readonly OAuthConsents $consents) {}

    public function index(Request $request): View
    {
        return view('sso.connected', ['apps' => $this->consents->forUser($request->user())]);
    }

    public function destroy(Request $request, string $client): RedirectResponse
    {
        $this->consents->withdraw($request->user(), $client);

        return redirect()->route('account.apps')->with('status', 'Ihre Zustimmung wurde widerrufen. Das System ist abgemeldet, und das Portal fragt bei der nächsten Anmeldung erneut nach Ihrer Zustimmung.');
    }
}

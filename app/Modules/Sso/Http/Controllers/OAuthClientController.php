<?php

namespace App\Modules\Sso\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sso\Services\OAuthClients;
use App\Modules\Sso\Services\OAuthKeys;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/** Anbindungen für Nextcloud, Moodle und andere Systeme verwalten (Berechtigung oauth.manage, nur Administration). */
class OAuthClientController extends Controller
{
    public function __construct(private readonly OAuthClients $clients, private readonly OAuthKeys $keys) {}

    public function index(): View
    {
        $clients = $this->clients->all();

        return view('sso.index', ['clients' => $clients, 'settings' => $clients->mapWithKeys(fn ($client): array => [(string) $client->getKey() => $this->clients->settings($client)]), 'keys' => $this->keys->status()]);
    }

    public function create(): View
    {
        return view('sso.form', ['client' => null, 'setting' => null, 'roleOptions' => OAuthClients::roleOptions(), 'defaultRoles' => OAuthClients::defaultRoles()]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $client = $this->clients->create($this->input($request), $request->user());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()->route('admin.oauth.show', $client->getKey())->with('oauth_secret', $client->plainSecret)->with('status', 'Die Anbindung wurde angelegt.');
    }

    public function show(Request $request, string $client): View
    {
        $model = $this->find($client);

        return view('sso.show', ['client' => $model, 'setting' => $this->clients->settings($model), 'secret' => $request->session()->get('oauth_secret'), 'roleOptions' => OAuthClients::roleOptions()]);
    }

    public function edit(string $client): View
    {
        $model = $this->find($client);

        return view('sso.form', ['client' => $model, 'setting' => $this->clients->settings($model), 'roleOptions' => OAuthClients::roleOptions(), 'defaultRoles' => OAuthClients::defaultRoles()]);
    }

    public function update(Request $request, string $client): RedirectResponse
    {
        $model = $this->find($client);

        try {
            $this->clients->update($model, $this->input($request), $request->user());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()->route('admin.oauth.show', $model->getKey())->with('status', 'Die Anbindung wurde gespeichert.');
    }

    public function renewSecret(Request $request, string $client): RedirectResponse
    {
        $model = $this->find($client);
        $request->validate(['password' => ['required', 'current_password']], ['password.required' => 'Bitte geben Sie Ihr Passwort ein.', 'password.current_password' => 'Das Passwort ist nicht korrekt.']);

        return redirect()->route('admin.oauth.show', $model->getKey())->with('oauth_secret', $this->clients->renewSecret($model, $request->user()))->with('status', 'Das Geheimnis wurde erneuert. Das alte gilt nicht mehr.');
    }

    public function block(Request $request, string $client): RedirectResponse
    {
        $model = $this->find($client);
        $blocked = $request->input('action') !== 'unblock';
        $this->clients->setBlocked($model, $blocked, $request->user());

        return redirect()->route('admin.oauth.index')->with('status', $blocked ? 'Die Anbindung ist gesperrt, alle Anmeldungen wurden beendet.' : 'Die Anbindung ist wieder freigegeben.');
    }

    public function destroy(Request $request, string $client): RedirectResponse
    {
        $model = $this->find($client);
        $request->validate(['password' => ['required', 'current_password']], ['password.required' => 'Bitte geben Sie Ihr Passwort ein.', 'password.current_password' => 'Das Passwort ist nicht korrekt.']);
        $this->clients->delete($model, $request->user());

        return redirect()->route('admin.oauth.index')->with('status', 'Die Anbindung wurde gelöscht.');
    }

    public function createKeys(Request $request): RedirectResponse
    {
        try {
            $created = $this->keys->ensure();
        } catch (Throwable $exception) {
            return back()->withErrors(['keys' => $exception->getMessage()]);
        }

        return redirect()->route('admin.oauth.index')->with('status', $created ? 'Die Schlüssel wurden erzeugt.' : 'Die Schlüssel sind bereits vorhanden.');
    }

    public function renewKeys(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], ['password.required' => 'Bitte geben Sie Ihr Passwort ein.', 'password.current_password' => 'Das Passwort ist nicht korrekt.']);

        try {
            $this->keys->renew($request->user());
        } catch (Throwable $exception) {
            return back()->withErrors(['keys' => $exception->getMessage()]);
        }

        return redirect()->route('admin.oauth.index')->with('status', 'Die Schlüssel wurden erneuert. Alle Anmeldungen über das Portal laufen ab; die Nutzenden melden sich einmal neu an.');
    }

    /** @return array<string, mixed> */
    private function input(Request $request): array
    {
        return [
            'name' => $request->input('name'),
            'redirect_uris' => $request->input('redirect_uris'),
            'confidential' => $request->boolean('confidential'),
            'roles' => (array) $request->input('roles', []),
            'description' => $request->input('description'),
            'contact' => $request->input('contact'),
            'show_consent' => $request->boolean('show_consent'),
        ];
    }

    private function find(string $id): \Laravel\Passport\Client
    {
        return $this->clients->find($id) ?? abort(404);
    }
}

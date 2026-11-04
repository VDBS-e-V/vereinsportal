<?php

namespace App\Modules\Setup\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Setup\Services\Setup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * Einrichtung im Browser (Webhosting ohne Konsole). Schritte: Voraussetzungen, Schlüssel, Datenbank, erste Administration, Abschluss. Es gibt
 * keine Sitzung: Jede Seite sendet den Token als verstecktes Feld mit. Nach dem Abschluss schreibt der letzte Schritt die Sperrdatei, danach gibt es
 * die Adresse nicht mehr (404).
 */
class SetupController extends Controller
{
    public function __construct(private readonly Setup $setup) {}

    public function show(): View
    {
        abort_unless($this->setup->isOpen(), 404);

        return view('setup.token', ['error' => null]);
    }

    public function handle(Request $request): View|Response
    {
        abort_unless($this->setup->isOpen(), 404);

        $token = (string) $request->input('token');

        if (! $this->setup->tokenValid($token)) {
            return response()->view('setup.token', ['error' => 'Der Token stimmt nicht.'], 403);
        }

        return match ((string) $request->input('step', 'check')) {
            'key' => $this->key($token),
            'database' => $this->database($request, $token),
            'admin' => $this->admin($request, $token),
            'finish' => $this->finish($token),
            default => $this->check($request, $token),
        };
    }

    private function check(Request $request, string $token): View
    {
        $rows = $this->setup->requirements($request->isSecure());

        return view('setup.check', ['token' => $token, 'rows' => $rows, 'blocked' => $this->setup->hasErrors($rows)]);
    }

    private function key(string $token): View
    {
        $has = filled(config('app.key'));

        return view('setup.key', ['token' => $token, 'has' => $has, 'newKey' => $has ? null : $this->setup->newKey()]);
    }

    private function database(Request $request, string $token): View
    {
        $result = $request->boolean('run') ? $this->setup->migrate() : null;

        return view('setup.database', ['token' => $token, 'result' => $result]);
    }

    private function admin(Request $request, string $token): View
    {
        if ($this->setup->administrationExists()) {
            return view('setup.admin', ['token' => $token, 'exists' => true, 'created' => false, 'errors' => [], 'input' => []]);
        }

        if (! $request->boolean('create')) {
            return view('setup.admin', ['token' => $token, 'exists' => false, 'created' => false, 'errors' => [], 'input' => []]);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email', 'unique:people,email'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'password' => ['required', 'confirmed', 'min:12', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/\d/'],
        ], [
            'first_name.required' => 'Bitte geben Sie den Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie den Nachnamen ein.',
            'email.required' => 'Bitte geben Sie die E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse ist schon vergeben.',
            'birth_date.required' => 'Bitte geben Sie das Geburtsdatum ein.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'password.required' => 'Bitte vergeben Sie ein Passwort.',
            'password.confirmed' => 'Die beiden Passwörter stimmen nicht überein.',
            'password.min' => 'Das Passwort braucht mindestens 12 Zeichen.',
            'password.regex' => 'Das Passwort braucht Groß- und Kleinbuchstaben und mindestens eine Ziffer.',
        ]);

        if ($validator->fails()) {
            return view('setup.admin', ['token' => $token, 'exists' => false, 'created' => false, 'errors' => $validator->errors()->all(), 'input' => $request->only(['first_name', 'last_name', 'email', 'birth_date'])]);
        }

        $this->setup->createAdministration($validator->validated());

        return view('setup.admin', ['token' => $token, 'exists' => false, 'created' => true, 'errors' => [], 'input' => []]);
    }

    private function finish(string $token): View
    {
        $keys = $this->setup->passportKeys();
        $locked = $this->setup->administrationExists() && $this->setup->lock();

        return view('setup.finish', ['token' => $token, 'keys' => $keys, 'locked' => $locked, 'hasAdmin' => $this->setup->administrationExists()]);
    }
}

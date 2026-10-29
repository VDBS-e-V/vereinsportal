<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Konto > Profil: Stammdaten der Person ansehen. Name und Geburtsdatum pflegt die Geschäftsstelle;
 * Telefon und Anschrift kann das Mitglied selbst ändern.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $this->user($request);

        return view('account.profile', ['user' => $user, 'person' => $user->person]);
    }

    public function update(Request $request): RedirectResponse
    {
        $person = $this->user($request)->person;

        abort_if($person === null, 404);

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
        ], [
            'country_code.size' => 'Bitte geben Sie den Länderkürzel mit zwei Buchstaben an, z. B. DE.',
            'country_code.alpha' => 'Bitte geben Sie den Länderkürzel mit zwei Buchstaben an, z. B. DE.',
            'country_code.required' => 'Bitte geben Sie den Länderkürzel mit zwei Buchstaben an, z. B. DE.',
        ]);

        $data['country_code'] = strtoupper($data['country_code']);

        $person->fill($data)->save();

        $changed = array_values(array_diff(array_keys($person->getChanges()), ['updated_at']));

        if ($changed !== []) {
            AuditLog::record(AuditEvent::ProfileUpdated, $this->user($request), $person, ['felder' => $changed]);
        }

        return redirect()->route('account.profile')->with('success', 'Ihre Angaben wurden gespeichert.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

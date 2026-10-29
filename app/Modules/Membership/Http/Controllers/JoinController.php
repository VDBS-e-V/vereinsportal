<?php

namespace App\Modules\Membership\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Membership\Services\MembershipRegistrations;
use App\Modules\Membership\Services\OnlineApplications;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Öffentliche Seiten rund um die Mitgliedschaft (Bereich Start): Online-Antrag, E-Mail-Bestätigung und
 * Selbstregistrierung per Registrierungslink. Antworten verraten nie, ob es eine Adresse oder Person schon gibt.
 */
class JoinController extends Controller
{
    public function __construct(
        private readonly OnlineApplications $applications,
        private readonly MembershipRegistrations $registrations,
    ) {}

    public function apply(): View
    {
        return view('join.apply');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'statute' => ['accepted'],
            'privacy' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ], $this->messages());

        // Das versteckte Feld "website" füllen nur Programme aus: neutral antworten, nichts anlegen.
        if (! filled($request->input('website'))) {
            $this->applications->apply($data);
        }

        return redirect()->route('join.applied')->with('join_email', mb_strtolower(trim($data['email'])));
    }

    public function applied(): View
    {
        return view('join.applied');
    }

    public function verify(User $user, string $hash): View
    {
        return $this->applications->verify($user, $hash)
            ? view('join.verified')
            : view('join.link-invalid', ['reason' => 'Dieser Bestätigungslink ist ungültig.']);
    }

    public function resend(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:254']], ['email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.', 'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);

        $this->applications->resend($data['email']);

        return redirect()->route('join.applied')->with('success', 'Wenn es zu dieser Adresse einen unbestätigten Antrag gibt, haben wir den Link erneut gesendet.');
    }

    public function register(string $token): View
    {
        $membership = $this->registrations->find($token);

        if ($membership === null) {
            return view('join.link-invalid', ['reason' => 'Dieser Registrierungslink ist ungültig oder abgelaufen.']);
        }

        return view('join.register', ['membership' => $membership, 'token' => $token]);
    }

    public function registerStore(Request $request, string $token): RedirectResponse|View
    {
        $membership = $this->registrations->find($token);

        if ($membership === null) {
            return view('join.link-invalid', ['reason' => 'Dieser Registrierungslink ist ungültig oder abgelaufen.']);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
            'birth_date' => [$membership->birth_date === null ? 'required' : 'nullable', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\/\-.\s]+$/'],
            'street' => ['nullable', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'privacy' => ['accepted'],
        ], $this->messages());

        try {
            $this->registrations->register($membership, $data);
        } catch (DomainException $exception) {
            return back()->withInput($request->except('password', 'password_confirmation'))->withErrors(['password' => $exception->getMessage()]);
        }

        return redirect()->route('login')->with('success', 'Ihr Konto ist angelegt. Sie können sich jetzt anmelden.');
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'first_name.required' => 'Bitte geben Sie Ihren Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie Ihren Nachnamen ein.',
            'birth_date.required' => 'Bitte geben Sie Ihr Geburtsdatum ein.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'birth_date.after' => 'Bitte prüfen Sie Ihr Geburtsdatum.',
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'password.required' => 'Bitte wählen Sie ein Passwort.',
            'password.confirmed' => 'Die beiden Passwörter stimmen nicht überein.',
            'password.min' => 'Das Passwort muss mindestens 12 Zeichen lang sein.',
            'password.mixed' => 'Das Passwort braucht Groß- und Kleinbuchstaben.',
            'password.numbers' => 'Das Passwort braucht mindestens eine Ziffer.',
            'statute.accepted' => 'Bitte bestätigen Sie, dass Sie die Satzung anerkennen.',
            'privacy.accepted' => 'Bitte bestätigen Sie die Kenntnisnahme der Datenschutzhinweise.',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
        ];
    }
}

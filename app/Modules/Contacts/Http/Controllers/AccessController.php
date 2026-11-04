<?php

namespace App\Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\AccessCredential;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Contacts\Services\AccessCredentials;
use App\Modules\Contacts\Services\ContactAccessSender;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\TeamApplications;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Persönlicher Zugang ohne Konto: Anmelden per Link oder Code, Angaben, offene Seminare. Gilt nur für Bewerbungen. */
class AccessController extends Controller
{
    private const INVALID = 'Link oder Code sind nicht gültig. Prüfen Sie die Eingabe oder fordern Sie unten einen neuen Zugang an.';

    public function __construct(private readonly AccessCredentials $credentials, private readonly ContactAccessSender $sender) {}

    public function link(Request $request, string $token): RedirectResponse
    {
        $credential = $this->credentials->findByToken($token);

        if ($credential === null) {
            return redirect()->route('access.code')->withErrors(['code' => self::INVALID]);
        }

        return $this->enter($request, $credential);
    }

    public function codeForm(): View
    {
        return view('access.code');
    }

    public function codeVerify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'code' => ['required', 'string', 'max:30'],
        ], ['email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.', 'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'code.required' => 'Bitte geben Sie Ihren Code ein.']);

        if ($this->credentials->isBlocked($data['email'])) {
            return back()->withInput(['email' => $data['email']])->withErrors(['code' => 'Zu viele Fehlversuche. Bitte warten Sie eine Stunde oder fordern Sie einen neuen Zugang an.']);
        }

        $credential = $this->credentials->findByCode($data['email'], $data['code']);

        if ($credential === null) {
            return back()->withInput(['email' => $data['email']])->withErrors(['code' => self::INVALID]);
        }

        return $this->enter($request, $credential);
    }

    /** Schickt einen neuen Zugang an die Adresse, falls sie auf der Liste steht. Die Antwort ist immer dieselbe. */
    public function resend(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:190']], ['email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.', 'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);

        $contact = TeamContact::query()->where('email', mb_strtolower(trim($data['email'])))->first();

        if ($contact !== null) {
            $this->sender->queue($contact);
        }

        return redirect()->route('access.code')->with('success', 'Wenn die Adresse auf unserer Liste steht, haben wir Ihnen einen neuen Zugang geschickt.');
    }

    public function home(Request $request): View
    {
        /** @var TeamContact $contact */
        $contact = $request->attributes->get('contact');

        $mine = SeminarApplication::query()->where('contact_id', $contact->id)->with('seminar.partner')->latest('updated_at')->get();

        return view('access.home', [
            'contact' => $contact,
            'seminars' => app(TeamApplications::class)->open(),
            'mine' => $mine,
            'mineBySeminar' => $mine->keyBy('seminar_id'),
            'draft' => $request->session()->get('contact_access.draft', []),
            'competenceOptions' => (array) config('portal.team.competences', []),
        ]);
    }

    /** Bewerbung auf ein oder mehrere Seminare in einem Schritt (Mehrfachauswahl). */
    public function apply(Request $request): RedirectResponse
    {
        /** @var TeamContact $contact */
        $contact = $request->attributes->get('contact');
        $draft = (array) $request->session()->get('contact_access.draft', []);
        $first = trim((string) ($draft['first_name'] ?? $contact->first_name));
        $last = trim((string) ($draft['last_name'] ?? $contact->last_name));

        if ($first === '' || $last === '') {
            return back()->withErrors(['name' => 'Bitte tragen Sie zuerst unter „Ihre Angaben“ Ihren Vor- und Nachnamen ein.']);
        }

        $data = $request->validate([
            'seminar_ids' => ['required', 'array', 'min:1', 'max:50'],
            'seminar_ids.*' => ['integer'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_year' => ['nullable', 'integer', 'min:1930', 'max:'.((int) now()->format('Y') - 14)],
            'competences' => ['nullable', 'array'],
            'competences.*' => ['string', Rule::in(array_keys((array) config('portal.team.competences', [])))],
            'experience' => ['nullable', 'string', 'max:800'],
            'note' => ['nullable', 'string', 'max:300'],
            'wants_lead' => ['nullable', 'boolean'],
            'consent_contact' => ['nullable', 'boolean'],
            'consent_data' => ['accepted'],
            'save' => ['nullable', 'boolean'],
        ], [
            'seminar_ids.required' => 'Bitte haken Sie mindestens ein Seminar an.',
            'seminar_ids.min' => 'Bitte haken Sie mindestens ein Seminar an.',
            'birth_year.min' => 'Bitte prüfen Sie das Geburtsjahr.',
            'birth_year.max' => 'Bitte prüfen Sie das Geburtsjahr.',
            'consent_data.accepted' => 'Bitte stimmen Sie der Verarbeitung Ihrer Angaben zur Teambildung zu.',
        ]);

        $details = [
            'name' => $first.' '.$last,
            'phone' => $data['phone'] ?? ($draft['phone'] ?? $contact->phone),
            'birth_year' => $data['birth_year'] ?? null,
            'competences' => $data['competences'] ?? [],
            'experience' => $data['experience'] ?? null,
            'note' => $data['note'] ?? null,
            'wants_lead' => $request->boolean('wants_lead'),
            'consent_contact' => $request->boolean('consent_contact'),
        ];

        $team = app(TeamApplications::class);
        $done = 0;
        $problems = [];

        foreach (Seminar::query()->whereIn('id', $data['seminar_ids'])->get() as $seminar) {
            try {
                $team->applyContact($seminar, $contact, $details);
                $done++;
            } catch (DomainException $exception) {
                $problems[] = $seminar->title.': '.$exception->getMessage();
            }
        }

        if ($request->boolean('save')) {
            $contact->forceFill(['competences' => $details['competences'], 'profile_saved' => true] + ($details['phone'] ? ['phone' => $details['phone']] : []))->save();
        }

        $redirect = back();

        if ($problems !== []) {
            $redirect = $redirect->withErrors(['apply' => implode(' ', $problems)]);
        }

        return $done > 0 ? $redirect->with('success', $done === 1 ? 'Ihr Interesse wurde eingetragen.' : "Ihr Interesse an {$done} Seminaren wurde eingetragen.") : $redirect;
    }

    /** Eine oder mehrere Bewerbungen zurückziehen (Mehrfachauswahl). */
    public function withdraw(Request $request): RedirectResponse
    {
        /** @var TeamContact $contact */
        $contact = $request->attributes->get('contact');

        $data = $request->validate(['application_ids' => ['required', 'array', 'min:1'], 'application_ids.*' => ['integer']], ['application_ids.required' => 'Bitte haken Sie mindestens eine Bewerbung an.', 'application_ids.min' => 'Bitte haken Sie mindestens eine Bewerbung an.']);

        $team = app(TeamApplications::class);
        $done = 0;
        $problems = [];

        foreach (SeminarApplication::query()->where('contact_id', $contact->id)->whereIn('id', $data['application_ids'])->with('seminar')->get() as $application) {
            try {
                $team->withdrawContact($application);
                $done++;
            } catch (DomainException $exception) {
                $problems[] = $application->seminar->title.': '.$exception->getMessage();
            }
        }

        $redirect = back();

        if ($problems !== []) {
            $redirect = $redirect->withErrors(['withdraw' => implode(' ', $problems)]);
        }

        return $done > 0 ? $redirect->with('success', $done === 1 ? 'Die Bewerbung wurde zurückgezogen.' : "{$done} Bewerbungen wurden zurückgezogen.") : $redirect;
    }

    public function profile(Request $request): RedirectResponse
    {
        /** @var TeamContact $contact */
        $contact = $request->attributes->get('contact');

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:150'],
            'house_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'save' => ['nullable', 'boolean'],
        ], ['first_name.required' => 'Bitte geben Sie Ihren Vornamen ein.', 'last_name.required' => 'Bitte geben Sie Ihren Nachnamen ein.']);

        $values = array_map(fn ($value) => is_string($value) && trim($value) === '' ? null : (is_string($value) ? trim($value) : $value), collect($data)->except('save')->all());

        if ($request->boolean('save')) {
            $contact->forceFill($values + ['profile_saved' => true])->save();
            $request->session()->forget('contact_access.draft');

            return back()->with('success', 'Ihre Angaben wurden für die Zukunft gespeichert.');
        }

        // Ohne Häkchen gelten die Angaben nur für diese Sitzung (und später für die Bewerbung).
        $request->session()->put('contact_access.draft', $values);

        return back()->with('success', 'Ihre Angaben gelten für diesen Besuch. Sie werden nicht gespeichert.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('contact_access');

        return redirect()->route('home')->with('success', 'Sie sind abgemeldet.');
    }

    private function enter(Request $request, AccessCredential $credential): RedirectResponse
    {
        $this->credentials->touch($credential);
        $request->session()->regenerate();
        $request->session()->put('contact_access', ['credential' => $credential->id, 'at' => now()->getTimestamp()]);

        return redirect()->route('access.home');
    }
}

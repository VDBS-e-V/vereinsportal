<?php

namespace App\Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\AccessCredential;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Contacts\Services\AccessCredentials;
use App\Modules\Contacts\Services\ContactAccessSender;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Persönlicher Zugang ohne Konto: Anmelden per Link oder Code, Angaben, offene Seminare.  */
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

        return view('access.home', ['contact' => $contact]);
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

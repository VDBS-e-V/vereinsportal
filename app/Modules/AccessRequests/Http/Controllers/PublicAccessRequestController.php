<?php

namespace App\Modules\AccessRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AccessRequests\Enums\AccessConnection;
use App\Modules\AccessRequests\Services\AccessRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/** Öffentlicher Antrag auf Zugang zum Portal (Seite „Zugang zum Portal“): mehrstufiges Formular, Eingang, Danke-Seite. */
class PublicAccessRequestController extends Controller
{
    public function __construct(private readonly AccessRequests $requests) {}

    public function show(): View
    {
        return view('portal.access', ['connections' => AccessConnection::options()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'consent' => ['accepted'],
            'connection' => ['required', Rule::enum(AccessConnection::class)],
            'organization' => ['nullable', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:1000'],
            'proofs' => ['nullable', 'array', 'max:5'],
            'proofs.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\/\-.\s]+$/'],
            'terms' => ['accepted'],
            'website' => ['nullable'],
        ], [
            'consent.accepted' => 'Bitte stimmen Sie der Verarbeitung Ihrer Daten zu.',
            'connection.required' => 'Bitte wählen Sie Ihre Verbindung zum Verein.',
            'connection.enum' => 'Bitte wählen Sie eine Verbindung aus der Liste.',
            'proofs.max' => 'Bitte laden Sie höchstens fünf Nachweise hoch.',
            'proofs.*.mimes' => 'Nachweise müssen PDF-, JPEG- oder PNG-Dateien sein.',
            'proofs.*.max' => 'Jeder Nachweis darf höchstens 5 MB groß sein.',
            'proofs.*.file' => 'Bitte wählen Sie gültige Dateien als Nachweis aus.',
            'first_name.required' => 'Bitte geben Sie Ihren Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie Ihren Nachnamen ein.',
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'birth_date.required' => 'Bitte geben Sie Ihr Geburtsdatum an.',
            'birth_date.date' => 'Bitte geben Sie ein gültiges Geburtsdatum an.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'birth_date.after' => 'Bitte prüfen Sie das Geburtsdatum.',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
            'terms.accepted' => 'Bitte akzeptieren Sie die Nutzungsbedingungen und die Datenschutzerklärung.',
        ]);

        // Das versteckte Feld füllen nur Programme aus: neutral antworten, nichts anlegen.
        if (! filled($request->input('website'))) {
            try {
                $this->requests->submit($data, array_values((array) $request->file('proofs', [])));
            } catch (Throwable $exception) {
                report($exception);

                return back()->withInput($request->except('proofs'))->with('error', 'Ihr Antrag konnte gerade nicht gesendet werden. Bitte versuchen Sie es später erneut oder schreiben Sie uns über das Kontaktformular.');
            }
        }

        return redirect()->route('portal.access.thanks')->with('access_email', mb_strtolower(trim($data['email'])));
    }

    public function thanks(): View
    {
        return view('access-requests.thanks');
    }
}

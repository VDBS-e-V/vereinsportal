<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

/** Öffentliches Kontaktformular: Die Nachricht geht per E-Mail an den gewählten Empfänger (config portal.contact_form), gespeichert wird nichts. */
class ContactController extends Controller
{
    public function show(): View
    {
        return view('portal.contact', ['recipients' => $this->recipientOptions()]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'recipient' => ['required', Rule::in(array_keys((array) config('portal.contact_form.recipients')))],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'consent' => ['accepted'],
            'website' => ['nullable'],
        ], [
            'first_name.required' => 'Bitte geben Sie Ihren Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie Ihren Nachnamen ein.',
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'recipient.required' => 'Bitte wählen Sie einen Empfänger.',
            'recipient.in' => 'Bitte wählen Sie einen Empfänger aus der Liste.',
            'subject.required' => 'Bitte geben Sie einen Betreff ein.',
            'message.required' => 'Bitte schreiben Sie Ihre Nachricht.',
            'message.max' => 'Die Nachricht darf höchstens 5.000 Zeichen lang sein.',
            'consent.accepted' => 'Bitte stimmen Sie der Verarbeitung Ihrer Daten zu.',
        ]);

        // Das versteckte Feld füllen nur Programme aus: neutral antworten, nichts senden.
        if (filled($request->input('website'))) {
            return redirect()->route('portal.contact')->with('success', 'Danke, Ihre Nachricht wurde gesendet.');
        }

        $recipient = config('portal.contact_form.recipients.'.$data['recipient']);

        try {
            Mail::to($recipient['email'])->send(new ContactMessageMail(
                trim($data['first_name'].' '.$data['last_name']),
                mb_strtolower(trim($data['email'])),
                $recipient['label'],
                trim($data['subject']),
                trim($data['message']),
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Ihre Nachricht konnte gerade nicht gesendet werden. Bitte versuchen Sie es später erneut.');
        }

        return redirect()->route('portal.contact')->with('success', 'Danke, Ihre Nachricht wurde gesendet.');
    }

    /** @return array<string, string> */
    private function recipientOptions(): array
    {
        return collect((array) config('portal.contact_form.recipients'))->map(fn (array $recipient): string => $recipient['label'])->all();
    }
}

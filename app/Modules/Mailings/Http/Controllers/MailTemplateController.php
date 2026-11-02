<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Mailvorlage für Mitteilungen an Teamende (Geschäftsstelle): Betreff und Text mit Platzhaltern, {eigener_text} ist die Stelle für den Abschnitt der Koordination. */
class MailTemplateController extends Controller
{
    public function __construct(private readonly MailTemplates $templates) {}

    public function edit(): View
    {
        $template = $this->templates->get(MailTemplates::TEAM_SEMINAR);

        return view('mailings.template-edit', [
            'template' => $template,
            'body' => RichText::forEditor($template['body']),
            'customized' => $this->templates->isCustomized(MailTemplates::TEAM_SEMINAR),
            'placeholders' => MailPlaceholders::definitions('team'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
        ], ['subject.required' => 'Bitte geben Sie einen Betreff ein.', 'body.required' => 'Bitte schreiben Sie den Text.', 'body.max' => 'Der Text ist zu lang.']);

        $errors = [];

        foreach (['subject' => 'Betreff', 'body' => 'Text'] as $field => $label) {
            $unknown = MailPlaceholders::unknown($data[$field], 'team');

            if ($unknown !== []) {
                $errors[$field] = $label.': Unbekannte Platzhalter '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $unknown)).'.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        /** @var User $user */
        $user = Auth::user();
        $this->templates->save(MailTemplates::TEAM_SEMINAR, $data['subject'], $data['body'], $user);

        return redirect()->route('mailings.templates.edit')->with('success', 'Die Vorlage wurde gespeichert.');
    }

    public function reset(): RedirectResponse
    {
        $this->templates->reset(MailTemplates::TEAM_SEMINAR);

        return redirect()->route('mailings.templates.edit')->with('success', 'Die eingebaute Vorlage gilt wieder.');
    }
}

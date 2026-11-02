<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Mailvorlagen (Geschäftsstelle und Vorstand): eigene Vorlagen für Schreiben an Gruppen (anlegen, ändern, löschen) und der feste Rahmen für
 * Mitteilungen der Koordination an Teamende zu Seminaren ({eigener_text} ist die Stelle für den Abschnitt der Koordination).
 */
class MailTemplateController extends Controller
{
    public function __construct(private readonly MailTemplates $templates) {}

    public function index(): View
    {
        return view('mailings.template-index', [
            'templates' => $this->templates->general(),
            'team' => $this->templates->get(MailTemplates::TEAM_SEMINAR),
            'teamCustomized' => $this->templates->isCustomized(MailTemplates::TEAM_SEMINAR),
        ]);
    }

    public function create(): View
    {
        return view('mailings.template-form', ['template' => null, 'body' => null, 'placeholders' => MailPlaceholders::definitions('general')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, 'general');
        $template = $this->templates->createGeneral($data['name'], $data['description'] ?? null, $data['subject'], $data['body'], $this->user());

        return redirect()->route('mailings.templates.index')->with('success', 'Die Vorlage „'.$template->name.'“ wurde gespeichert.');
    }

    public function edit(MailTemplate $template): View
    {
        abort_unless($template->kind === MailTemplate::KIND_GENERAL, 404);

        return view('mailings.template-form', ['template' => $template, 'body' => RichText::forEditor($template->body), 'placeholders' => MailPlaceholders::definitions('general')]);
    }

    public function update(Request $request, MailTemplate $template): RedirectResponse
    {
        abort_unless($template->kind === MailTemplate::KIND_GENERAL, 404);

        $data = $this->validated($request, 'general');
        $this->templates->updateGeneral($template, $data['name'], $data['description'] ?? null, $data['subject'], $data['body'], $this->user());

        return redirect()->route('mailings.templates.index')->with('success', 'Die Vorlage wurde gespeichert.');
    }

    public function destroy(MailTemplate $template): RedirectResponse
    {
        abort_unless($template->kind === MailTemplate::KIND_GENERAL, 404);

        $template->delete();

        return redirect()->route('mailings.templates.index')->with('success', 'Die Vorlage wurde gelöscht.');
    }

    public function team(): View
    {
        $template = $this->templates->get(MailTemplates::TEAM_SEMINAR);

        return view('mailings.template-team', [
            'template' => $template,
            'body' => RichText::forEditor($template['body']),
            'customized' => $this->templates->isCustomized(MailTemplates::TEAM_SEMINAR),
            'placeholders' => MailPlaceholders::definitions('team'),
        ]);
    }

    public function updateTeam(Request $request): RedirectResponse
    {
        $data = $this->validated($request, 'team', false);
        $this->templates->save(MailTemplates::TEAM_SEMINAR, $data['subject'], $data['body'], $this->user());

        return redirect()->route('mailings.templates.team')->with('success', 'Die Vorlage wurde gespeichert.');
    }

    public function resetTeam(): RedirectResponse
    {
        $this->templates->reset(MailTemplates::TEAM_SEMINAR);

        return redirect()->route('mailings.templates.team')->with('success', 'Die eingebaute Vorlage gilt wieder.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $kind, bool $withName = true): array
    {
        $request->merge(['body' => RichText::normalize($request->input('body'))]);

        $rules = ['subject' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'max:20000']];
        $messages = ['subject.required' => 'Bitte geben Sie einen Betreff ein.', 'body.required' => 'Bitte schreiben Sie den Text.', 'body.max' => 'Der Text ist zu lang.'];

        if ($withName) {
            $rules += ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:250']];
            $messages += ['name.required' => 'Bitte geben Sie der Vorlage einen Namen.'];
        }

        $data = $request->validate($rules, $messages);
        $errors = [];

        foreach (['subject' => 'Betreff', 'body' => 'Text'] as $field => $label) {
            $unknown = MailPlaceholders::unknown($data[$field], $kind);

            if ($unknown !== []) {
                $errors[$field] = $label.': Unbekannte Platzhalter '.implode(', ', array_map(fn (string $key): string => '{'.$key.'}', $unknown)).'.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

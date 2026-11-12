<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Models\MailTemplateVersion;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Mailings\Support\MailCategories;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Mailvorlagen (Geschäftsstelle und Vorstand): eigene Vorlagen für Schreiben an Gruppen (anlegen, ändern, löschen) und der feste Rahmen für
 * Mitteilungen der Koordination an Teamende zu Seminaren ({eigener_text} ist die Stelle für den Abschnitt der Koordination).
 */
class MailTemplateController extends Controller
{
    public function __construct(private readonly MailTemplates $templates) {}

    public function index(Request $request): View
    {
        return view('mailings.template-index', [
            'templates' => $this->templates->general($request->query('q'), $request->query('kategorie')),
            'search' => (string) $request->query('q', ''),
            'category' => (string) $request->query('kategorie', ''),
            'team' => $this->templates->get(MailTemplates::TEAM_SEMINAR),
            'teamCustomized' => $this->templates->isCustomized(MailTemplates::TEAM_SEMINAR),
        ]);
    }

    public function create(): View
    {
        return view('mailings.template-form', ['categories' => MailCategories::TEMPLATES, 'audiences' => MailCategories::AUDIENCES, 'template' => null, 'body' => null, 'placeholders' => MailPlaceholders::definitions('general')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, 'general');
        $this->assertUniqueName($data['name'], null);
        $template = $this->templates->createGeneral($data['name'], $data['description'] ?? null, $data['subject'], $data['body'], $this->user(), $data['category'] ?? null, $data['audience'] ?? null);

        return redirect()->route('mailings.templates.index')->with('success', 'Die Vorlage „'.$template->name.'“ wurde gespeichert.');
    }

    public function edit(MailTemplate $template): View
    {
        abort_unless($template->kind === MailTemplate::KIND_GENERAL, 404);

        return view('mailings.template-form', ['categories' => MailCategories::TEMPLATES, 'audiences' => MailCategories::AUDIENCES, 'template' => $template, 'body' => RichText::forEditor($template->body), 'placeholders' => MailPlaceholders::definitions('general'), 'versions' => $template->versions()->with('savedBy.person')->get()]);
    }

    public function update(Request $request, MailTemplate $template): RedirectResponse
    {
        abort_unless($template->kind === MailTemplate::KIND_GENERAL, 404);

        $data = $this->validated($request, 'general');
        $this->assertUniqueName($data['name'], $template->id);
        $this->templates->updateGeneral($template, $data['name'], $data['description'] ?? null, $data['subject'], $data['body'], $this->user(), $data['category'] ?? null, $data['audience'] ?? null);

        return redirect()->route('mailings.templates.index')->with('success', 'Die Vorlage wurde gespeichert.');
    }

    public function restore(MailTemplate $template, MailTemplateVersion $version): RedirectResponse
    {
        abort_unless($template->kind === MailTemplate::KIND_GENERAL && $version->mail_template_id === $template->id, 404);

        $this->templates->restore($template, $version, $this->user());

        return redirect()->route('mailings.templates.edit', $template)->with('success', 'Der frühere Stand wurde wiederhergestellt.');
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
            $rules += ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:250'], 'category' => ['nullable', Rule::in(array_keys(MailCategories::TEMPLATES))], 'audience' => ['nullable', Rule::in(array_keys(MailCategories::AUDIENCES))]];
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

    /** Mehrfachauswahl: löscht die angehakten Vorlagen für Rundschreiben. */
    public function destroyMany(Request $request): RedirectResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        $count = $ids === [] ? 0 : MailTemplate::query()->where('kind', MailTemplate::KIND_GENERAL)->whereIn('id', $ids)->delete();

        return redirect()->route('mailings.templates.index')->with($count > 0 ? 'success' : 'error', $count > 0 ? $count.' Vorlage(n) gelöscht.' : 'Bitte wählen Sie mindestens eine Vorlage.');
    }

    private function assertUniqueName(string $name, ?int $ignoreId): void
    {
        if ($this->templates->nameTaken($name, $ignoreId)) {
            throw ValidationException::withMessages(['name' => 'Es gibt schon eine Vorlage mit diesem Namen.']);
        }
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

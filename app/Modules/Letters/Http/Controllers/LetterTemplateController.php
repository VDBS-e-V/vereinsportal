<?php

namespace App\Modules\Letters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Letters\Models\LetterTemplate;
use App\Modules\Letters\Services\LetterTemplates;
use App\Modules\Letters\Support\LetterVariables;
use App\Modules\Letters\Support\SampleLetter;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Briefvorlagen (Word mit Platzhaltern): ansehen und herunterladen für alle mit Recht letters.use, hochladen, ändern und löschen mit letters.manage. */
class LetterTemplateController extends Controller
{
    public function __construct(private readonly LetterTemplates $templates) {}

    public function index(): View
    {
        return view('letters.index', ['templates' => LetterTemplate::query()->orderBy('name')->get(), 'user' => $this->user(), 'service' => $this->templates]);
    }

    public function variables(): View
    {
        return view('letters.variables', ['groups' => LetterVariables::groups()]);
    }

    public function sample(): BinaryFileResponse
    {
        $path = SampleLetter::build(SampleLetter::sampleParagraphs());

        return response()->download($path, 'Beispielvorlage_Brief.docx')->deleteFileAfterSend();
    }

    /** Die Platzhalterliste als Word-Datei: zum Nachschlagen und zum Kopieren der Platzhalter in die eigene Vorlage. */
    public function variablesFile(): BinaryFileResponse
    {
        $lines = ['Platzhalter für Briefvorlagen', 'Kopieren Sie einen Platzhalter und fügen Sie ihn in Word mit „Nur Text übernehmen“ ein.', ''];

        foreach (LetterVariables::groups() as $group) {
            $lines[] = mb_strtoupper($group['label']);

            foreach ($group['items'] as $name => $meaning) {
                $lines[] = '${'.$name.'}  –  '.$meaning;
            }

            $lines[] = '';
        }

        return response()->download(SampleLetter::build($lines), 'Platzhalterliste.docx')->deleteFileAfterSend();
    }

    public function download(LetterTemplate $template): BinaryFileResponse
    {
        return response()->download($this->templates->absolutePath($template), $template->original_name);
    }

    public function create(): View
    {
        return view('letters.form', ['template' => null, 'audiences' => LetterTemplate::AUDIENCES, 'service' => $this->templates]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:250'],
            'audience' => ['required', Rule::in(array_keys(LetterTemplate::AUDIENCES))], 'file' => ['required', 'file'],
        ], ['name.required' => 'Bitte geben Sie der Vorlage einen Namen.', 'file.required' => 'Bitte wählen Sie die Word-Datei.']);

        try {
            $template = $this->templates->store($request->file('file'), $data['name'], $data['description'] ?? null, $data['audience'], $this->user());
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('letters.edit', $template)->with('success', 'Die Vorlage wurde gespeichert.');
    }

    public function edit(LetterTemplate $template): View
    {
        return view('letters.form', ['template' => $template, 'audiences' => LetterTemplate::AUDIENCES, 'service' => $this->templates]);
    }

    public function update(Request $request, LetterTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:250'],
            'audience' => ['required', Rule::in(array_keys(LetterTemplate::AUDIENCES))], 'file' => ['nullable', 'file'],
        ]);

        try {
            $this->templates->updateDetails($template, $data['name'], $data['description'] ?? null, $data['audience'], $this->user());

            if ($request->hasFile('file')) {
                $this->templates->replaceFile($template, $request->file('file'), $this->user());
            }
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('letters.edit', $template)->with('success', 'Die Vorlage wurde gespeichert.');
    }

    public function destroy(LetterTemplate $template): RedirectResponse
    {
        $this->templates->delete($template, $this->user());

        return redirect()->route('letters.index')->with('success', 'Die Vorlage wurde gelöscht.');
    }

    /** Mehrfachauswahl: löscht die angehakten Vorlagen. */
    public function destroyMany(Request $request): RedirectResponse
    {
        $count = 0;

        foreach (LetterTemplate::query()->whereIn('id', array_filter(array_map('intval', (array) $request->input('ids', []))))->get() as $template) {
            $this->templates->delete($template, $this->user());
            $count++;
        }

        return redirect()->route('letters.index')->with($count > 0 ? 'success' : 'error', $count > 0 ? $count.' Vorlage(n) gelöscht.' : 'Bitte wählen Sie mindestens eine Vorlage.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

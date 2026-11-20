<?php

namespace App\Modules\Faq\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Faq\Models\FaqCategory;
use App\Modules\Faq\Models\FaqEntry;
use App\Support\Html\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Die Fragen einer FAQ-Kategorie: Tabelle mit Mehrfachauswahl und eigene Unterseiten zum Anlegen und Bearbeiten. Berechtigung faq.manage. */
class FaqEntryController extends Controller
{
    public function index(FaqCategory $category): View
    {
        return view('faq-admin.entries', [
            'category' => $category,
            'entries' => $category->entries()->get(),
            'others' => FaqCategory::query()->whereKeyNot($category->id)->orderBy('sort_order')->orderBy('title')->pluck('title', 'id')->all(),
        ]);
    }

    public function create(FaqCategory $category): View
    {
        $next = (int) $category->entries()->max('sort_order') + 10;

        return view('faq-admin.entry-form', ['category' => $category, 'entry' => new FaqEntry(['is_active' => true, 'sort_order' => $next])]);
    }

    public function store(Request $request, FaqCategory $category): RedirectResponse
    {
        $entry = $category->entries()->create($this->validated($request));

        AuditLog::record(AuditEvent::FaqEntrySaved, $this->user(), null, ['kategorie' => $category->title, 'frage' => mb_substr($entry->question, 0, 80)]);

        return redirect()->route('faq-admin.entries.index', $category)->with('success', 'Die Frage wurde angelegt.');
    }

    public function edit(FaqCategory $category, FaqEntry $entry): View
    {
        $this->own($category, $entry);

        return view('faq-admin.entry-form', ['category' => $category, 'entry' => $entry]);
    }

    public function update(Request $request, FaqCategory $category, FaqEntry $entry): RedirectResponse
    {
        $this->own($category, $entry);
        $entry->update($this->validated($request));

        AuditLog::record(AuditEvent::FaqEntrySaved, $this->user(), null, ['kategorie' => $category->title, 'frage' => mb_substr($entry->question, 0, 80)]);

        return redirect()->route('faq-admin.entries.index', $category)->with('success', 'Die Frage wurde gespeichert.');
    }

    public function destroy(FaqCategory $category, FaqEntry $entry): RedirectResponse
    {
        $this->own($category, $entry);
        $question = mb_substr($entry->question, 0, 80);
        $entry->delete();

        AuditLog::record(AuditEvent::FaqEntryDeleted, $this->user(), null, ['kategorie' => $category->title, 'frage' => $question]);

        return redirect()->route('faq-admin.entries.index', $category)->with('success', 'Die Frage wurde gelöscht.');
    }

    /** Mehrfachauswahl: sichtbar machen, ausblenden, in eine andere Kategorie verschieben oder löschen. */
    public function bulk(Request $request, FaqCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'aktion' => ['required', Rule::in(['activate', 'deactivate', 'move', 'delete'])],
            'ziel' => ['nullable', 'integer', Rule::exists('faq_categories', 'id')],
        ], ['ids.required' => 'Bitte wählen Sie mindestens eine Frage aus.']);

        if ($data['aktion'] === 'move' && (blank($data['ziel'] ?? null) || (int) $data['ziel'] === $category->id)) {
            return back()->withErrors(['ziel' => 'Bitte wählen Sie die Kategorie, in die die Fragen verschoben werden sollen.']);
        }

        $entries = $category->entries()->whereIn('id', $data['ids'])->get();

        foreach ($entries as $entry) {
            match ($data['aktion']) {
                'delete' => $entry->delete(),
                'move' => $entry->update(['faq_category_id' => (int) $data['ziel']]),
                default => $entry->update(['is_active' => $data['aktion'] === 'activate']),
            };
        }

        $count = $entries->count();
        $noun = $count === 1 ? 'Frage' : 'Fragen';

        AuditLog::record($data['aktion'] === 'delete' ? AuditEvent::FaqEntryDeleted : AuditEvent::FaqEntrySaved, $this->user(), null, ['kategorie' => $category->title, 'anzahl' => $count, 'aktion' => $data['aktion']]);

        return redirect()->route('faq-admin.entries.index', $category)->with('success', match ($data['aktion']) {
            'delete' => $count.' '.$noun.' gelöscht.',
            'move' => $count.' '.$noun.' verschoben.',
            'activate' => $count.' '.$noun.' sichtbar gemacht.',
            default => $count.' '.$noun.' ausgeblendet.',
        });
    }

    private function own(FaqCategory $category, FaqEntry $entry): void
    {
        abort_unless($entry->faq_category_id === $category->id, 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['answer' => RichText::normalize($request->input('answer'))]);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:250'],
            'answer' => ['required', 'string', 'max:20000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'question.required' => 'Bitte geben Sie die Frage ein.',
            'answer.required' => 'Bitte geben Sie die Antwort ein.',
            'answer.max' => 'Die Antwort darf höchstens 20.000 Zeichen lang sein.',
        ]);

        return [
            'question' => trim($data['question']),
            'answer' => RichText::clean($data['answer']) ?? '',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}

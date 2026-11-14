<?php

namespace App\Modules\Faq\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Faq\Models\FaqCategory;
use App\Modules\Faq\Models\FaqEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Verwaltung der FAQ: Kategorien mit ihren Fragen und Antworten. Berechtigung faq.manage. */
class FaqAdminController extends Controller
{
    public function index(): View
    {
        return view('faq-admin.index', ['categories' => FaqCategory::query()->withCount('entries')->orderBy('sort_order')->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('faq-admin.form', ['category' => new FaqCategory(['is_active' => true, 'in_faq_page' => true, 'sort_order' => 0]), 'entries' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$attributes, $entries] = $this->validated($request);

        $category = DB::transaction(function () use ($attributes, $entries): FaqCategory {
            $category = FaqCategory::query()->create($attributes);
            $this->syncEntries($category, $entries);

            return $category;
        });

        AuditLog::record(AuditEvent::FaqCategorySaved, $this->user(), null, ['kategorie' => $category->title, 'fragen' => count($entries)]);

        return redirect()->route('faq-admin.index')->with('success', 'Die Kategorie „'.$category->title.'“ wurde angelegt.');
    }

    public function edit(FaqCategory $category): View
    {
        return view('faq-admin.form', [
            'category' => $category,
            'entries' => $category->entries()->get(['question', 'answer'])->map(fn ($entry): array => ['question' => $entry->question, 'answer' => $entry->answer])->all(),
        ]);
    }

    public function update(Request $request, FaqCategory $category): RedirectResponse
    {
        [$attributes, $entries] = $this->validated($request);

        DB::transaction(function () use ($category, $attributes, $entries): void {
            $category->update($attributes);
            $this->syncEntries($category, $entries);
        });

        AuditLog::record(AuditEvent::FaqCategorySaved, $this->user(), null, ['kategorie' => $category->title, 'fragen' => count($entries)]);

        return redirect()->route('faq-admin.index')->with('success', 'Die Kategorie „'.$category->title.'“ wurde gespeichert.');
    }

    public function destroy(FaqCategory $category): RedirectResponse
    {
        $title = $category->title;
        $category->delete();
        AuditLog::record(AuditEvent::FaqCategoryDeleted, $this->user(), null, ['kategorie' => $title]);

        return redirect()->route('faq-admin.index')->with('success', 'Die Kategorie „'.$title.'“ wurde gelöscht.');
    }

    /** Mehrfachauswahl: sichtbar machen, ausblenden oder löschen. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'aktion' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
        ], ['ids.required' => 'Bitte wählen Sie mindestens eine Kategorie aus.']);

        $done = 0;

        foreach (FaqCategory::query()->whereIn('id', $data['ids'])->get() as $category) {
            if ($data['aktion'] === 'delete') {
                $title = $category->title;
                $category->delete();
                AuditLog::record(AuditEvent::FaqCategoryDeleted, $this->user(), null, ['kategorie' => $title]);
            } else {
                $category->update(['is_active' => $data['aktion'] === 'activate']);
                AuditLog::record(AuditEvent::FaqCategorySaved, $this->user(), null, ['kategorie' => $category->title]);
            }

            $done++;
        }

        $noun = $done === 1 ? 'Kategorie' : 'Kategorien';

        return redirect()->route('faq-admin.index')->with('success', match ($data['aktion']) {
            'delete' => $done.' '.$noun.' gelöscht.',
            'activate' => $done.' '.$noun.' sichtbar gemacht.',
            default => $done.' '.$noun.' ausgeblendet.',
        });
    }

    /** @return array{0: array<string, mixed>, 1: list<array{question: string, answer: string}>} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:250'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'entries' => ['nullable', 'array', 'max:100'],
            'entries.*.question' => ['nullable', 'string', 'max:250'],
            'entries.*.answer' => ['nullable', 'string', 'max:5000'],
        ], ['title.required' => 'Bitte geben Sie eine Bezeichnung ein.']);

        $entries = [];
        $incomplete = false;

        foreach ((array) ($data['entries'] ?? []) as $row) {
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            if ($question === '' || $answer === '') {
                $incomplete = true;

                continue;
            }

            $entries[] = ['question' => $question, 'answer' => $answer];
        }

        if ($incomplete) {
            throw ValidationException::withMessages(['entries' => 'Zu jeder Frage gehört eine Antwort und umgekehrt.']);
        }

        return [[
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'in_faq_page' => $request->boolean('in_faq_page'),
            'is_active' => $request->boolean('is_active'),
        ], $entries];
    }

    /** @param list<array{question: string, answer: string}> $entries */
    private function syncEntries(FaqCategory $category, array $entries): void
    {
        FaqEntry::query()->where('faq_category_id', $category->id)->delete();

        foreach ($entries as $index => $entry) {
            $category->entries()->create($entry + ['sort_order' => ($index + 1) * 10]);
        }
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}

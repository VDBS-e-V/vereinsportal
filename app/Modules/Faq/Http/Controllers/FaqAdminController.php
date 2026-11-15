<?php

namespace App\Modules\Faq\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Faq\Models\FaqCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Verwaltung der FAQ: Kategorien mit ihren Fragen und Antworten. Berechtigung faq.manage. */
class FaqAdminController extends Controller
{
    public function index(): View
    {
        return view('faq-admin.index', ['categories' => FaqCategory::query()->withCount('entries')->orderBy('sort_order')->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('faq-admin.form', ['category' => new FaqCategory(['is_active' => true, 'in_faq_page' => true, 'sort_order' => 0])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = FaqCategory::query()->create($this->validated($request));

        AuditLog::record(AuditEvent::FaqCategorySaved, $this->user(), null, ['kategorie' => $category->title]);

        return redirect()->route('faq-admin.entries.index', $category)->with('success', 'Die Kategorie „'.$category->title.'“ wurde angelegt. Legen Sie jetzt die Fragen an.');
    }

    public function edit(FaqCategory $category): View
    {
        return view('faq-admin.form', ['category' => $category]);
    }

    public function update(Request $request, FaqCategory $category): RedirectResponse
    {
        $category->update($this->validated($request));

        AuditLog::record(AuditEvent::FaqCategorySaved, $this->user(), null, ['kategorie' => $category->title]);

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

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:250'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], ['title.required' => 'Bitte geben Sie eine Bezeichnung ein.']);

        return [
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'in_faq_page' => $request->boolean('in_faq_page'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}

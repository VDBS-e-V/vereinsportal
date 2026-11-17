<?php

namespace App\Modules\Faq\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Faq\Models\FaqCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return view('faq-admin.form', ['category' => new FaqCategory(['is_active' => true, 'sort_order' => 0, 'audience' => 'public']), 'roleOptions' => $this->roleOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = FaqCategory::query()->create($this->validated($request));

        AuditLog::record(AuditEvent::FaqCategorySaved, $this->user(), null, ['kategorie' => $category->title]);

        return redirect()->route('faq-admin.entries.index', $category)->with('success', 'Die Kategorie „'.$category->title.'“ wurde angelegt. Legen Sie jetzt die Fragen an.');
    }

    public function edit(FaqCategory $category): View
    {
        return view('faq-admin.form', ['category' => $category, 'roleOptions' => $this->roleOptions()]);
    }

    public function update(Request $request, FaqCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

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
    private function validated(Request $request, ?FaqCategory $category = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:250'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'audience' => ['nullable', Rule::in(array_keys(FaqCategory::AUDIENCES))],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in(array_map(fn (RoleKey $role): string => $role->value, array_filter(RoleKey::cases(), fn (RoleKey $role): bool => $role !== RoleKey::Guest)))],
        ], ['title.required' => 'Bitte geben Sie eine Bezeichnung ein.']);

        // Ohne Angabe bleibt die bisherige Einstellung (neue Kategorien: öffentlich)
        $audience = $data['audience'] ?? data_get($category, 'audience') ?? 'public';

        if ($audience === 'roles' && blank($data['roles'] ?? data_get($category, 'roles') ?? [])) {
            throw ValidationException::withMessages(['roles' => 'Bitte wählen Sie mindestens eine Rolle, die die Kategorie sehen darf.']);
        }

        return [
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'audience' => $audience,
            'roles' => $audience === 'roles' ? array_values(array_unique($data['roles'] ?? data_get($category, 'roles') ?? [])) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /** @return array<string, string> */
    private function roleOptions(): array
    {
        return collect(RoleKey::cases())->reject(fn (RoleKey $role): bool => $role === RoleKey::Guest)->mapWithKeys(fn (RoleKey $role): array => [$role->value => $role->label()])->all();
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}

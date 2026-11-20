<?php

namespace App\Modules\ContactForms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\ContactForms\Models\ContactForm;
use App\Modules\ContactForms\Models\ContactTopic;
use App\Modules\Faq\Models\FaqCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Verwaltung der Themen des öffentlichen Kontaktbereichs, einschließlich der FAQ-Seiten (Fragen und Antworten je Thema). Berechtigung contact_forms.manage. */
class ContactTopicAdminController extends Controller
{
    public function index(): View
    {
        $roots = ContactTopic::query()->whereNull('parent_id')->with(['form', 'faqCategory.entries', 'children.form', 'children.faqCategory.entries'])->orderBy('sort_order')->orderBy('title')->get();

        return view('contact-admin.topics-index', ['roots' => $roots, 'standard' => ContactForm::standard()]);
    }

    public function create(Request $request): View
    {
        $topic = new ContactTopic(['action' => $request->query('art', 'form'), 'is_active' => true, 'sort_order' => 0, 'parent_id' => $request->integer('bereich') ?: null]);

        return view('contact-admin.topic-form', $this->formData($topic));
    }

    public function store(Request $request): RedirectResponse
    {
        $topic = ContactTopic::query()->create($this->validated($request, null));

        AuditLog::record(AuditEvent::ContactTopicSaved, $this->user(), null, ['thema' => $topic->title, 'art' => $topic->action]);

        return redirect()->route('contact-admin.topics.index')->with('success', 'Das Thema „'.$topic->title.'“ wurde angelegt.');
    }

    public function edit(ContactTopic $topic): View
    {
        return view('contact-admin.topic-form', $this->formData($topic));
    }

    public function update(Request $request, ContactTopic $topic): RedirectResponse
    {
        $topic->update($this->validated($request, $topic));

        AuditLog::record(AuditEvent::ContactTopicSaved, $this->user(), null, ['thema' => $topic->title, 'art' => $topic->action]);

        return redirect()->route('contact-admin.topics.index')->with('success', 'Das Thema „'.$topic->title.'“ wurde gespeichert.');
    }

    public function destroy(ContactTopic $topic): RedirectResponse
    {
        if ($topic->children()->exists()) {
            return back()->with('error', 'Der Bereich „'.$topic->title.'“ enthält noch Themen. Verschieben oder löschen Sie diese zuerst.');
        }

        $title = $topic->title;
        $topic->delete();
        AuditLog::record(AuditEvent::ContactTopicDeleted, $this->user(), null, ['thema' => $title]);

        return redirect()->route('contact-admin.topics.index')->with('success', 'Das Thema „'.$title.'“ wurde gelöscht.');
    }

    /** Mehrfachauswahl: aktivieren, ausblenden oder löschen. Bereiche mit Themen bleiben beim Löschen stehen. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'aktion' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
        ], ['ids.required' => 'Bitte wählen Sie mindestens ein Thema aus.']);

        $topics = ContactTopic::query()->whereIn('id', $data['ids'])->get();
        $done = 0;
        $kept = 0;

        foreach ($topics as $topic) {
            if ($data['aktion'] === 'delete') {
                if ($topic->children()->exists()) {
                    $kept++;

                    continue;
                }

                $topic->delete();
                AuditLog::record(AuditEvent::ContactTopicDeleted, $this->user(), null, ['thema' => $topic->title]);
            } else {
                $topic->update(['is_active' => $data['aktion'] === 'activate']);
                AuditLog::record(AuditEvent::ContactTopicSaved, $this->user(), null, ['thema' => $topic->title, 'art' => $topic->action]);
            }

            $done++;
        }

        $message = match ($data['aktion']) {
            'delete' => $done.' '.($done === 1 ? 'Thema wurde' : 'Themen wurden').' gelöscht.',
            'activate' => $done.' '.($done === 1 ? 'Thema ist' : 'Themen sind').' jetzt sichtbar.',
            default => $done.' '.($done === 1 ? 'Thema ist' : 'Themen sind').' jetzt ausgeblendet.',
        };

        if ($kept > 0) {
            $message .= ' '.$kept.' '.($kept === 1 ? 'Bereich enthält' : 'Bereiche enthalten').' noch Themen und '.($kept === 1 ? 'blieb' : 'blieben').' stehen.';
        }

        return redirect()->route('contact-admin.topics.index')->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function formData(ContactTopic $topic): array
    {
        return [
            'topic' => $topic,
            'categories' => ContactTopic::query()->where('action', 'category')->when($topic->exists, fn ($query) => $query->whereKeyNot($topic->id))->orderBy('sort_order')->orderBy('title')->pluck('title', 'id')->all(),
            'forms' => ContactForm::query()->orderBy('name')->pluck('name', 'id')->all(),
            'faqCategories' => FaqCategory::query()->orderBy('sort_order')->orderBy('title')->pluck('title', 'id')->all(),
            'hasChildren' => $topic->exists && $topic->children()->exists(),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?ContactTopic $topic): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:250'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'action' => ['required', Rule::in(array_keys(ContactTopic::ACTIONS))],
            'parent_id' => ['nullable', 'integer', Rule::exists('contact_topics', 'id')->where('action', 'category')],
            'contact_form_id' => ['nullable', 'integer', 'exists:contact_forms,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'faq_category_id' => ['nullable', 'integer', 'exists:faq_categories,id'],
        ], [
            'title.required' => 'Bitte geben Sie eine Bezeichnung ein.',
            'action.required' => 'Bitte wählen Sie, wohin das Thema führt.',
            'parent_id.exists' => 'Bitte wählen Sie einen vorhandenen Bereich.',
        ]);

        $errors = [];
        $action = $data['action'];

        if ($action === 'category' && filled($data['parent_id'] ?? null)) {
            $errors['parent_id'] = 'Ein Bereich steht immer oben und kann nicht in einem anderen Bereich liegen.';
        }

        if ($topic !== null && (int) ($data['parent_id'] ?? 0) === $topic->id) {
            $errors['parent_id'] = 'Ein Thema kann nicht in sich selbst liegen.';
        }

        if ($topic !== null && $action !== 'category' && $topic->children()->exists()) {
            $errors['action'] = 'Dieser Bereich enthält noch Themen. Verschieben oder löschen Sie diese zuerst.';
        }

        if ($action === 'form' && blank($data['contact_form_id'] ?? null)) {
            $errors['contact_form_id'] = 'Bitte wählen Sie das Formular, zu dem das Thema führt.';
        }

        if ($action === 'faq' && blank($data['faq_category_id'] ?? null)) {
            $errors['faq_category_id'] = 'Bitte wählen Sie die FAQ-Kategorie, zu der das Thema führt. Neue Kategorien legen Sie unter Geschäftsstelle › FAQ an.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'keywords' => filled($data['keywords'] ?? null) ? trim($data['keywords']) : null,
            'action' => $action,
            'parent_id' => $action === 'category' ? null : ($data['parent_id'] ?? null),
            'contact_form_id' => $action === 'form' ? ($data['contact_form_id'] ?? null) : null,
            'faq_category_id' => $action === 'faq' ? ($data['faq_category_id'] ?? null) : null,
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
